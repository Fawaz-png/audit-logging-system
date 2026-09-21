<?php

namespace App\Services;


use App\Enums\AuditAssignmentType;
use App\Models\Audit;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class AuditService
{
    public function create(array $data): Audit
    {
        return DB::transaction(function () use ($data) {
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

            return $audit->load([
                'creator',
                'department',
                'auditors',
            ]);
        });
    }

    public function update(Audit $audit, array $data): Audit
    {
        return DB::transaction(function () use ($audit, $data) {
            $leadAuditorId = $data['lead_auditor_id'];
            $supportingAuditorIds = $data['supporting_auditor_ids'] ?? [];
            $auditeeIds = $data['auditee_ids'] ?? [];

            unset(
                $data['lead_auditor_id'],
                $data['supporting_auditor_ids'],
                $data['auditee_ids']
            );

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

            return $audit->load([
                'creator',
                'department',
                'auditors',
            ]);
        });
    }
}