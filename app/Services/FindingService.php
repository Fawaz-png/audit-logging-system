<?php

namespace App\Services;

use App\Models\Audit;
use App\Models\Finding;
use App\Models\FindingAction;
use App\Models\FindingComment;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class FindingService
{
    public function __construct(
        private FindingWorkflowNotifier $notifier,
        private AuditLogger $auditLogger
    ) {}

    public function create(
        Audit $audit,
        array $data,
        User $user
    ): Finding {
        return DB::transaction(function () use ($audit, $data, $user) {
            unset($data['actions']);
            unset($data['evidence_file'], $data['evidence_description']);

            $finding = $audit->findings()->create([
                ...$data,
                'uuid' => (string) Str::uuid(),
                'status' => Finding::STATUS_OPEN,
                'created_by' => $user->id,
                'updated_by' => $user->id,
            ]);

            $this->auditLogger->record('FINDING_REGISTERED', $finding, $user, null, [
                'reference' => $finding->reference,
                'audit_id' => $finding->audit_id,
                'status' => $finding->status,
            ]);

            $this->notifier->notifyDepartmentAuditees(
                $finding,
                $user,
                'New finding logged for your department',
                'A finding from a concluded audit has been registered for your department.'
            );

            return $finding->load([
                'audit',
                'creator',
                'updater',
            ]);
        });
    }

    public function update(
        Finding $finding,
        array $data,
        User $user
    ): Finding {
        return DB::transaction(function () use ($finding, $data, $user) {
            unset($data['actions']);
            unset($data['removed_action_ids']);

            $finding->update([
                ...$data,
                'updated_by' => $user->id,
            ]);

            $this->auditLogger->record('AUDITEE_RESPONSE_SUBMITTED', $finding, $user, null, [
                'status' => $finding->status,
                'response_at' => $finding->response_at?->toIso8601String(),
            ]);

            return $finding->fresh([
                'audit',
                'creator',
                'updater',
                'actions.responsibleDepartment',
                'actions.responsibleUser',
            ]);
        });
    }

    public function delete(Finding $finding): void
    {
        DB::transaction(function () use ($finding) {
            $finding->delete();
        });
    }

    /*
    |--------------------------------------------------------------------------
    | Response
    |--------------------------------------------------------------------------
    */

    public function respond(
        Finding $finding,
        User $user,
        string $response
    ): Finding {
        return DB::transaction(function () use ($finding, $user, $response) {
            $this->ensureStatus(
                $finding,
                [Finding::STATUS_OPEN],
                'This finding is no longer awaiting an initial response.'
            );

            $finding->update([
                'auditee_response' => $response,
                'response_by' => $user->id,
                'response_at' => now(),
                'status' => Finding::STATUS_RESPONSE_RECEIVED,
                'updated_by' => $user->id,
            ]);

            $this->notifier->notifyParticipants(
                $finding,
                $user,
                'Auditee response submitted',
                'An auditee response was added to the finding.'
            );

            return $finding->fresh([
                'audit',
                'responseBy',
            ]);
        });
    }

    /*
    |--------------------------------------------------------------------------
    | Discussion
    |--------------------------------------------------------------------------
    */

    public function addComment(
        Finding $finding,
        User $user,
        string $body
    ): FindingComment {
        return DB::transaction(function () use ($finding, $user, $body) {
            $comment = $finding->comments()->create([
                'uuid' => (string) Str::uuid(),
                'user_id' => $user->id,
                'body' => $body,
            ]);

            $this->auditLogger->record('FINDING_COMMENT_ADDED', $comment, $user, null, [
                'finding_id' => $finding->id,
                'comment_id' => $comment->id,
            ]);

            $this->notifier->notifyParticipants(
                $finding,
                $user,
                'New finding discussion message',
                'A participant added a message to the finding discussion.'
            );

            return $comment;
        });
    }

    /*
    |--------------------------------------------------------------------------
    | Corrective action
    |--------------------------------------------------------------------------
    */

    public function proposeAction(
        Finding $finding,
        User $user,
        array $data
    ): FindingAction {
        return DB::transaction(function () use ($finding, $user, $data) {
            $this->ensureStatus(
                $finding,
                [Finding::STATUS_RESPONSE_RECEIVED],
                'A corrective action can only be proposed after the finding has received a response.'
            );

            $existingAction = $finding->actions()
                ->whereIn('status', [
                    FindingAction::STATUS_DRAFT,
                    FindingAction::STATUS_PENDING_APPROVAL,
                    FindingAction::STATUS_APPROVED,
                    FindingAction::STATUS_IN_PROGRESS,
                ])
                ->exists();

            if ($existingAction) {
                throw ValidationException::withMessages([
                    'action' => 'This finding already has an active corrective action.',
                ]);
            }

            $action = $finding->actions()->create([
                'uuid' => (string) Str::uuid(),
                'responsible_department_id' => $data['responsible_department_id'],
                'responsible_user_id' => $data['responsible_user_id'],
                'action' => $data['action'],
                'proposed_due_date' => $data['proposed_due_date'],
                'status' => FindingAction::STATUS_PENDING_APPROVAL,
                'created_by' => $user->id,
                'updated_by' => $user->id,
            ]);

            $this->auditLogger->record('CORRECTIVE_ACTION_SUBMITTED', $action, $user, null, [
                'finding_id' => $finding->id,
                'proposed_due_date' => $action->proposed_due_date?->toDateString(),
                'status' => $action->status,
            ]);

            $finding->update([
                'status' => Finding::STATUS_ACTION_PENDING_APPROVAL,
                'updated_by' => $user->id,
            ]);

            $this->notifier->notifyLeadAuditors(
                $finding,
                $user,
                'Corrective action submitted for approval',
                'The auditee submitted a corrective action and proposed SLA date for review.'
            );

            return $action->load([
                'responsibleDepartment',
                'responsibleUser',
                'creator',
            ]);
        });
    }

    public function approveAction(
        FindingAction $action,
        User $user,
        string $agreedDueDate,
        ?string $comment = null
    ): FindingAction {
        return DB::transaction(function () use ($action, $user, $agreedDueDate, $comment) {
            $finding = $action->finding;

            if (
                $action->status !== FindingAction::STATUS_PENDING_APPROVAL
            ) {
                throw ValidationException::withMessages([
                    'action' => 'This corrective action is not awaiting approval.',
                ]);
            }

            $action->update([
                'agreed_due_date' => $agreedDueDate,
                'status' => FindingAction::STATUS_IN_PROGRESS,
                'approved_by' => $user->id,
                'approved_at' => now(),
                'approval_comment' => $comment,
                'updated_by' => $user->id,
            ]);

            $this->auditLogger->record('CORRECTIVE_ACTION_APPROVED_SLA_STARTED', $action, $user, [
                'status' => FindingAction::STATUS_PENDING_APPROVAL,
            ], [
                'status' => $action->status,
                'agreed_due_date' => $action->agreed_due_date?->toDateString(),
            ]);

            $finding->update([
                'status' => Finding::STATUS_REMEDIATION_IN_PROGRESS,
                'updated_by' => $user->id,
            ]);

            $this->notifier->notifyParticipants(
                $finding,
                $user,
                'Corrective action approved; SLA started',
                'The corrective action was approved. The remediation SLA is due '.$agreedDueDate.'.'
            );

            return $action->fresh([
                'finding',
                'responsibleDepartment',
                'responsibleUser',
                'approver',
            ]);
        });
    }

    public function rejectAction(
        FindingAction $action,
        User $user,
        string $reason
    ): FindingAction {
        return DB::transaction(function () use ($action, $user, $reason) {
            $finding = $action->finding;

            if (
                $action->status !== FindingAction::STATUS_PENDING_APPROVAL
            ) {
                throw ValidationException::withMessages([
                    'action' => 'This corrective action is not awaiting approval.',
                ]);
            }

            $action->update([
                'status' => FindingAction::STATUS_REJECTED,
                'rejection_reason' => $reason,
                'updated_by' => $user->id,
            ]);

            $this->auditLogger->record('CORRECTIVE_ACTION_REJECTED', $action, $user, [
                'status' => FindingAction::STATUS_PENDING_APPROVAL,
            ], [
                'status' => $action->status,
                'reason' => $reason,
            ]);

            $finding->update([
                'status' => Finding::STATUS_RESPONSE_RECEIVED,
                'updated_by' => $user->id,
            ]);

            $this->notifier->notifyParticipants(
                $finding,
                $user,
                'Corrective action needs revision',
                'The auditor rejected the proposed corrective action. Review the discussion and submit a revised proposal.'
            );

            return $action->fresh([
                'finding',
                'responsibleDepartment',
                'responsibleUser',
            ]);
        });
    }

    /*
    |--------------------------------------------------------------------------
    | Remediation
    |--------------------------------------------------------------------------
    */

    public function startRemediation(
        FindingAction $action,
        User $user
    ): FindingAction {
        return DB::transaction(function () use ($action, $user) {
            if ($action->status !== FindingAction::STATUS_APPROVED) {
                throw ValidationException::withMessages([
                    'action' => 'Only an approved corrective action can enter remediation.',
                ]);
            }

            $action->update([
                'status' => FindingAction::STATUS_IN_PROGRESS,
                'updated_by' => $user->id,
            ]);

            $action->finding->update([
                'status' => Finding::STATUS_REMEDIATION_IN_PROGRESS,
                'updated_by' => $user->id,
            ]);

            return $action->fresh([
                'finding',
            ]);
        });
    }

    public function completeRemediation(
        FindingAction $action,
        User $user
    ): FindingAction {
        return DB::transaction(function () use ($action, $user) {
            if (
                ! in_array($action->status, [
                    FindingAction::STATUS_APPROVED,
                    FindingAction::STATUS_IN_PROGRESS,
                ], true)
            ) {
                throw ValidationException::withMessages([
                    'action' => 'This corrective action cannot be marked as completed.',
                ]);
            }

            $action->update([
                'status' => FindingAction::STATUS_COMPLETED,
                'completed_at' => now(),
                'updated_by' => $user->id,
            ]);

            $this->auditLogger->record('REMEDIATION_MARKED_COMPLETE', $action, $user, null, [
                'finding_id' => $action->finding_id,
                'completed_at' => $action->completed_at?->toIso8601String(),
            ]);

            $action->finding->update([
                'status' => Finding::STATUS_READY_FOR_VERIFICATION,
                'updated_by' => $user->id,
            ]);

            $this->notifier->notifyParticipants(
                $action->finding,
                $user,
                'Remediation marked complete',
                'Remediation is complete. Submit a closure request with resolution evidence for auditor review.'
            );

            return $action->fresh([
                'finding',
                'responsibleDepartment',
                'responsibleUser',
            ]);
        });
    }

    /*
    |--------------------------------------------------------------------------
    | Verification
    |--------------------------------------------------------------------------
    */

    public function verify(
        Finding $finding,
        User $user,
        string $notes
    ): Finding {
        return DB::transaction(function () use ($finding, $user, $notes) {
            $this->ensureStatus(
                $finding,
                [Finding::STATUS_READY_FOR_VERIFICATION],
                'Only findings ready for verification can be verified.'
            );

            $action = $finding->actions()
                ->where('status', FindingAction::STATUS_COMPLETED)
                ->latest()
                ->first();

            if (! $action) {
                throw ValidationException::withMessages([
                    'finding' => 'A completed corrective action is required before verification.',
                ]);
            }

            $hasResolutionEvidence = $finding->resolutionEvidences()->exists();

            if (! $hasResolutionEvidence) {
                throw ValidationException::withMessages([
                    'evidence' => 'Resolution evidence is required before a finding can be verified.',
                ]);
            }

            $finding->update([
                'status' => Finding::STATUS_VERIFIED,
                'verified_by' => $user->id,
                'verified_at' => now(),
                'verification_notes' => $notes,
                'updated_by' => $user->id,
            ]);

            return $finding->fresh([
                'audit',
                'verifier',
                'actions',
                'resolutionEvidences',
            ]);
        });
    }

    /*
    |--------------------------------------------------------------------------
    | Closure
    |--------------------------------------------------------------------------
    */

    public function close(
        Finding $finding,
        User $user
    ): Finding {
        return DB::transaction(function () use ($finding, $user) {
            $this->ensureStatus(
                $finding,
                [Finding::STATUS_VERIFIED],
                'Only verified findings can be closed.'
            );

            $finding->update([
                'status' => Finding::STATUS_CLOSED,
                'closed_by' => $user->id,
                'closed_at' => now(),
                'updated_by' => $user->id,
            ]);

            return $finding->fresh([
                'audit',
                'verifier',
                'closer',
            ]);
        });
    }

    /*
    |--------------------------------------------------------------------------
    | Helpers
    |--------------------------------------------------------------------------
    */

    private function ensureStatus(
        Finding $finding,
        array $allowedStatuses,
        string $message
    ): void {
        if (! in_array($finding->status, $allowedStatuses, true)) {
            throw ValidationException::withMessages([
                'finding' => $message,
            ]);
        }
    }
}
