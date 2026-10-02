<?php

namespace App\Services;


use App\Enums\AuditAssignmentType;
use App\Models\Audit;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class AuditService
{
    public function __construct(private AuditLogger $auditLogger) {}

    public function create(array $data, User $actor): Audit
    {
        return DB::transaction(function () use ($data, $actor) {
            $leadAuditorId = $data['lead_auditor_id'];
            $supportingAuditorIds = $data['supporting_auditor_ids'] ?? [];
            $auditeeIds = $data['auditee_ids'] ?? [];

            unset(
                $data['lead_auditor_id'],
                $data['supporting_auditor_ids'],
                $data['auditee_ids']
            );

            $audit = Audit::create($data);

            $audit->auditors()->attach(
                $leadAuditorId,
                [
                    'uuid' => (string) Str::uuid(),
                    'assignment_type' => AuditAssignmentType::LEAD_AUDITOR->value,
                ]
            );

            foreach ($supportingAuditorIds as $userId) {
                $audit->auditors()->attach(
                    $userId,
                    [
                        'uuid' => (string) Str::uuid(),
                        'assignment_type' => AuditAssignmentType::SUPPORTING_AUDITOR->value,
                    ]
                );
            }

            foreach ($auditeeIds as $userId) {
                $audit->auditors()->attach(
                    $userId,
                    [
                        'uuid' => (string) Str::uuid(),
                        'assignment_type' => AuditAssignmentType::AUDITEE->value,
                    ]
                );
            }

            $this->auditLogger->recordChanges(
                'AUDIT_CREATED',
                $audit,
                $actor,
                [],
                $this->snapshot($audit)
            );

            return $audit->load([
                'creator',
                'department',
                'auditors',
            ]);
        });
    }

    public function update(Audit $audit, array $data, User $actor): Audit
    {
        return DB::transaction(function () use ($audit, $data, $actor) {
            $leadAuditorId = $data['lead_auditor_id'];
            $supportingAuditorIds = $data['supporting_auditor_ids'] ?? [];
            $auditeeIds = $data['auditee_ids'] ?? [];

            unset(
                $data['lead_auditor_id'],
                $data['supporting_auditor_ids'],
                $data['auditee_ids']
            );

            $before = $this->snapshot($audit);

            $audit->update($data);

            $existingAssignments = $audit->auditors()
                ->get()
                ->keyBy('id');

            $assignments = [];

            $assignments[$leadAuditorId] = [
                'uuid' => $existingAssignments[$leadAuditorId]?->pivot->uuid
                    ?? (string) Str::uuid(),
                'assignment_type' => AuditAssignmentType::LEAD_AUDITOR->value,
            ];

            foreach ($supportingAuditorIds as $userId) {
                $assignments[$userId] = [
                    'uuid' => $existingAssignments[$userId]?->pivot->uuid
                        ?? (string) Str::uuid(),
                    'assignment_type' => AuditAssignmentType::SUPPORTING_AUDITOR->value,
                ];
            }

            foreach ($auditeeIds as $userId) {
                $assignments[$userId] = [
                    'uuid' => $existingAssignments[$userId]?->pivot->uuid
                        ?? (string) Str::uuid(),
                    'assignment_type' => AuditAssignmentType::AUDITEE->value,
                ];
            }

            $audit->auditors()->sync($assignments);

            $this->auditLogger->recordChanges(
                'AUDIT_UPDATED',
                $audit,
                $actor,
                $before,
                $this->snapshot($audit)
            );

            return $audit->load([
                'creator',
                'department',
                'auditors',
            ]);
        });
    }

    /** @return array<string, mixed> */
    private function snapshot(Audit $audit): array
    {
        return [
            'reference' => $audit->reference,
            'title' => $audit->title,
            'objective' => $audit->objective,
            'scope' => $audit->scope,
            'department_id' => $audit->department_id,
            'audit_mode' => $audit->audit_mode,
            'methodology' => $audit->methodology,
            'location' => $audit->location,
            'previous_audit_reference' => $audit->previous_audit_reference,
            'start_date' => $audit->start_date?->toDateString(),
            'end_date' => $audit->end_date?->toDateString(),
            'status' => $audit->status,
            'assignments' => $audit->auditors()
                ->get(['users.id'])
                ->map(static fn (User $user): array => [
                    'user_id' => $user->id,
                    'assignment_type' => $user->pivot->assignment_type,
                ])
                ->sortBy('user_id')
                ->values()
                ->all(),
        ];
    }
}