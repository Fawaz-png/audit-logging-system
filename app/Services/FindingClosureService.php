<?php

namespace App\Services;

use App\Models\Finding;
use App\Models\FindingEvidence;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;
use Throwable;

class FindingClosureService
{
    public function __construct(
        private FindingEvidenceService $evidenceService,
        private FindingWorkflowNotifier $notifier,
        private AuditLogger $auditLogger
    ) {}

    public function request(
        Finding $finding,
        User $user,
        array $data,
        UploadedFile $file
    ): Finding {
        $storedPath = null;

        try {
            return DB::transaction(function () use ($finding, $user, $data, $file, &$storedPath): Finding {
                $lockedFinding = Finding::query()
                    ->lockForUpdate()
                    ->findOrFail($finding->id);

                $previousStatus = $lockedFinding->status;

                if (! in_array($lockedFinding->status, [
                    Finding::STATUS_READY_FOR_VERIFICATION,
                    Finding::STATUS_CLOSURE_REJECTED,
                ], true)) {
                    throw ValidationException::withMessages([
                        'finding' => 'This finding is not ready for a closure request.',
                    ]);
                }

                $evidence = $this->evidenceService->store(
                    $lockedFinding,
                    $user,
                    [
                        'type' => FindingEvidence::TYPE_RESOLUTION,
                        'description' => $data['description'] ?? null,
                    ],
                    $file
                );
                $storedPath = [$evidence->disk, $evidence->path];

                $lockedFinding->update([
                    'status' => Finding::STATUS_CLOSURE_REQUESTED,
                    'closure_requested_by' => $user->id,
                    'closure_requested_at' => now(),
                    'closure_reviewed_by' => null,
                    'closure_reviewed_at' => null,
                    'closure_request_notes' => $data['notes'],
                    'closure_review_notes' => null,
                    'closure_rejection_reason' => null,
                    'updated_by' => $user->id,
                ]);

                $this->auditLogger->record('FINDING_CLOSURE_REQUESTED', $lockedFinding, $user, [
                    'status' => $previousStatus,
                ], [
                    'status' => $lockedFinding->status,
                    'closure_request_notes' => $lockedFinding->closure_request_notes,
                    'closure_requested_by' => $user->id,
                    'closure_requested_at' => $lockedFinding->closure_requested_at?->toIso8601String(),
                    'evidence_id' => $evidence->id,
                ]);

                $this->notifier->notifyLeadAuditors(
                    $lockedFinding,
                    $user,
                    'Finding closure request submitted',
                    'The auditee submitted a closure request with resolution evidence.'
                );

                return $lockedFinding->fresh([
                    'closureRequester',
                    'resolutionEvidences',
                ]);
            });
        } catch (Throwable $exception) {
            if ($storedPath !== null) {
                [$disk, $path] = $storedPath;
                Storage::disk($disk)->delete($path);
            }

            throw $exception;
        }
    }

    public function approve(Finding $finding, User $user, ?string $notes): Finding
    {
        return DB::transaction(function () use ($finding, $user, $notes): Finding {
            $lockedFinding = Finding::query()
                ->lockForUpdate()
                ->findOrFail($finding->id);

            $this->ensureClosureRequest($lockedFinding);

            if (! $lockedFinding->resolutionEvidences()->exists()) {
                throw ValidationException::withMessages([
                    'evidence' => 'Resolution evidence is required before approving closure.',
                ]);
            }

            $before = [
                'status' => $lockedFinding->status,
                'closure_request_notes' => $lockedFinding->closure_request_notes,
            ];

            $lockedFinding->update([
                'status' => Finding::STATUS_CLOSED,
                'closed_by' => $user->id,
                'closed_at' => now(),
                'closure_reviewed_by' => $user->id,
                'closure_request_notes' => $lockedFinding->closure_request_notes,
                'closure_reviewed_at' => now(),
                'closure_review_notes' => $notes,
                'closure_rejection_reason' => null,
                'updated_by' => $user->id,
            ]);

            $this->auditLogger->recordChanges('FINDING_CLOSURE_APPROVED', $lockedFinding, $user, $before, [
                'status' => $lockedFinding->status,
                'closure_request_notes' => $lockedFinding->closure_request_notes,
                'closed_at' => $lockedFinding->closed_at?->toIso8601String(),
                'closure_reviewed_by' => $user->id,
                'closure_reviewed_at' => $lockedFinding->closure_reviewed_at?->toIso8601String(),
                'closure_review_notes' => $lockedFinding->closure_review_notes,
            ]);

            $this->notifier->notifyParticipants(
                $lockedFinding,
                $user,
                'Finding closure approved',
                'The auditor approved the closure request and closed the finding.'
            );

            return $lockedFinding->fresh(['closureRequester', 'closureReviewer', 'closer']);
        });
    }

    public function reject(Finding $finding, User $user, string $reason): Finding
    {
        return DB::transaction(function () use ($finding, $user, $reason): Finding {
            $lockedFinding = Finding::query()
                ->lockForUpdate()
                ->findOrFail($finding->id);

            $this->ensureClosureRequest($lockedFinding);

            $before = [
                'status' => $lockedFinding->status,
                'closure_request_notes' => $lockedFinding->closure_request_notes,
            ];

            $lockedFinding->update([
                'status' => Finding::STATUS_CLOSURE_REJECTED,
                'closure_reviewed_by' => $user->id,
                'closure_request_notes' => $lockedFinding->closure_request_notes,
                'closure_reviewed_at' => now(),
                'closure_review_notes' => null,
                'closure_rejection_reason' => $reason,
                'updated_by' => $user->id,
            ]);

            $this->auditLogger->recordChanges('FINDING_CLOSURE_REJECTED', $lockedFinding, $user, $before, [
                'status' => $lockedFinding->status,
                'closure_request_notes' => $lockedFinding->closure_request_notes,
                'closure_reviewed_by' => $user->id,
                'closure_reviewed_at' => $lockedFinding->closure_reviewed_at?->toIso8601String(),
                'closure_rejection_reason' => $lockedFinding->closure_rejection_reason,
            ]);

            $this->notifier->notifyParticipants(
                $lockedFinding,
                $user,
                'Finding closure request rejected',
                'The auditor returned the closure request with a reason. Review the finding and resubmit when ready.'
            );

            return $lockedFinding->fresh(['closureRequester', 'closureReviewer']);
        });
    }

    private function ensureClosureRequest(Finding $finding): void
    {
        if ($finding->status !== Finding::STATUS_CLOSURE_REQUESTED) {
            throw ValidationException::withMessages([
                'finding' => 'This finding has no pending closure request.',
            ]);
        }
    }
}
