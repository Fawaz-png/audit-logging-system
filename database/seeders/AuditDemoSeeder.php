<?php

namespace Database\Seeders;

use App\Models\Audit;
use App\Models\Department;
use App\Models\Finding;
use App\Models\FindingAction;
use App\Models\FindingEvidence;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class AuditDemoSeeder extends Seeder
{
    public function run(): void
    {
        $leadAuditor = User::query()->where('email', 'lead.auditor@example.com')->firstOrFail();
        $supportingAuditor = User::query()->where('email', 'supporting.auditor@example.com')->firstOrFail();
        $statuses = [
            Finding::STATUS_OPEN,
            Finding::STATUS_RESPONSE_RECEIVED,
            Finding::STATUS_ACTION_PENDING_APPROVAL,
            Finding::STATUS_REMEDIATION_IN_PROGRESS,
            Finding::STATUS_READY_FOR_VERIFICATION,
            Finding::STATUS_CLOSURE_REQUESTED,
            Finding::STATUS_CLOSURE_REJECTED,
            Finding::STATUS_VERIFIED,
            Finding::STATUS_CLOSED,
        ];
        $riskRatings = ['MEDIUM', 'HIGH', 'CRITICAL', 'LOW'];
        $auditDefinitions = [
            ['code' => 'IT', 'title' => 'Core Banking Access & Change Controls', 'status' => 'IN_PROGRESS', 'processes' => ['Privileged access recertification', 'Production change approvals', 'Service account ownership']],
            ['code' => 'FIN', 'title' => 'Financial Close & Reconciliation', 'status' => 'COMPLETED', 'processes' => ['General ledger reconciliations', 'Journal entry approvals', 'Financial reporting evidence']],
            ['code' => 'OPS', 'title' => 'Branch and Operations Resilience', 'status' => 'IN_PROGRESS', 'processes' => ['Branch cash balancing', 'Operational incident escalation', 'Business continuity exercises']],
            ['code' => 'HR', 'title' => 'Workforce Lifecycle Controls', 'status' => 'PLANNED', 'processes' => ['Joiner and leaver processing', 'Payroll change approvals', 'Sensitive personnel records']],
            ['code' => 'CBK', 'title' => 'Corporate Banking Credit Administration', 'status' => 'IN_PROGRESS', 'processes' => ['Relationship approval limits', 'Trade finance document review', 'Corporate credit covenant monitoring']],
            ['code' => 'TRS', 'title' => 'Treasury Liquidity and Markets', 'status' => 'PLANNED', 'processes' => ['Liquidity buffer monitoring', 'Market dealing confirmations', 'Treasury settlement controls']],
            ['code' => 'RCO', 'title' => 'Risk Governance and Financial Crime', 'status' => 'COMPLETED', 'processes' => ['Operational risk event governance', 'Sanctions alert disposition', 'Regulatory obligation tracking']],
            ['code' => 'RBT', 'title' => 'Retail Banking and Branch Controls', 'status' => 'IN_PROGRESS', 'processes' => ['Customer onboarding verification', 'Branch override monitoring', 'Customer complaint resolution']],
            ['code' => 'ENT', 'title' => 'Enterprise Services and Continuity', 'status' => 'PLANNED', 'processes' => ['Critical facility access', 'Business continuity ownership', 'Records retention controls']],
            ['code' => 'SEC', 'title' => 'Information Security Operations', 'status' => 'IN_PROGRESS', 'processes' => ['Security event triage', 'Identity governance reviews', 'Vulnerability remediation oversight']],
            ['code' => 'DCH', 'title' => 'Digital Banking Channel Assurance', 'status' => 'PLANNED', 'processes' => ['Mobile release governance', 'Online banking authentication', 'Digital service availability']],
            ['code' => 'LND', 'title' => 'Lending and Credit Underwriting', 'status' => 'IN_PROGRESS', 'processes' => ['Credit decision authority', 'Loan servicing changes', 'Collateral valuation review']],
            ['code' => 'PAY', 'title' => 'Payments and Settlement Integrity', 'status' => 'IN_PROGRESS', 'processes' => ['Card payment exception handling', 'Settlement account reconciliation', 'Payment release authorization']],
            ['code' => 'PRC', 'title' => 'Third-Party and Procurement Risk', 'status' => 'PLANNED', 'processes' => ['Vendor due diligence', 'Contract approval segregation', 'Critical supplier monitoring']],
            ['code' => 'LEG', 'title' => 'Legal and Regulatory Governance', 'status' => 'COMPLETED', 'processes' => ['Regulatory filing sign-off', 'Legal matter access controls', 'Corporate record retention']],
        ];

        foreach ($auditDefinitions as $auditIndex => $definition) {
            $department = Department::query()->where('code', $definition['code'])->firstOrFail();
            $auditees = User::query()
                ->where('department_id', $department->id)
                ->where('active', true)
                ->whereHas('role', fn ($query) => $query->where('slug', 'auditee'))
                ->orderBy('name')
                ->limit(3)
                ->get();
            $auditReference = 'AUD-'.$definition['code'].'-2026-01';
            $audit = Audit::query()->firstOrCreate(
                ['reference' => $auditReference],
                [
                    'uuid' => (string) Str::uuid(),
                    'title' => $definition['title'],
                    'objective' => 'Assess the design and operating effectiveness of key controls within '.$department->name.'.',
                    'scope' => 'Governance, transaction processing, access management, and follow-up controls.',
                    'department_id' => $department->id,
                    'audit_mode' => $auditIndex % 3 === 0 ? 'REMOTE' : 'HYBRID',
                    'methodology' => 'RISK_BASED',
                    'location' => 'Lagos, Nigeria',
                    'previous_audit_reference' => $auditIndex > 0 ? 'AUD-'.$definition['code'].'-2025-01' : null,
                    'start_date' => now()->subWeeks(($auditIndex + 1) * 2)->toDateString(),
                    'end_date' => $definition['status'] === 'COMPLETED' ? now()->subDays($auditIndex + 1)->toDateString() : null,
                    'status' => $definition['status'],
                    'created_by' => $leadAuditor->id,
                ]
            );

            $assignments = [
                $leadAuditor->id => [
                    'uuid' => (string) Str::uuid(),
                    'assignment_type' => 'LEAD_AUDITOR',
                ],
                $supportingAuditor->id => [
                    'uuid' => (string) Str::uuid(),
                    'assignment_type' => 'SUPPORTING_AUDITOR',
                ],
            ];

            foreach ($auditees as $auditee) {
                $assignments[$auditee->id] = [
                    'uuid' => (string) Str::uuid(),
                    'assignment_type' => 'AUDITEE',
                ];
            }

            $audit->auditors()->syncWithoutDetaching($assignments);

            foreach ($definition['processes'] as $findingIndex => $process) {
                $sequence = ($auditIndex * 3) + $findingIndex;
                $status = $statuses[$sequence % count($statuses)];
                $riskRating = $riskRatings[($auditIndex + $findingIndex) % count($riskRatings)];
                $auditee = $auditees->get($findingIndex % max($auditees->count(), 1));
                $hasResponse = $status !== Finding::STATUS_OPEN;
                $hasClosureRequest = in_array($status, [
                    Finding::STATUS_CLOSURE_REQUESTED,
                    Finding::STATUS_CLOSURE_REJECTED,
                    Finding::STATUS_VERIFIED,
                    Finding::STATUS_CLOSED,
                ], true);
                $isReviewed = in_array($status, [
                    Finding::STATUS_CLOSURE_REJECTED,
                    Finding::STATUS_VERIFIED,
                    Finding::STATUS_CLOSED,
                ], true);
                $isVerified = in_array($status, [Finding::STATUS_VERIFIED, Finding::STATUS_CLOSED], true);
                $isClosed = $status === Finding::STATUS_CLOSED;
                $findingReference = sprintf('FND-%s-2026-%02d', $definition['code'], $findingIndex + 1);

                $finding = Finding::query()->firstOrCreate(
                    ['reference' => $findingReference],
                    [
                        'uuid' => (string) Str::uuid(),
                        'audit_id' => $audit->id,
                        'title' => $process.' control weakness',
                        'description' => 'Testing identified inconsistent operation or retained evidence for '.$process.'.',
                        'criteria' => 'The approved control standard requires documented ownership, timely operation, and reviewable evidence.',
                        'effect' => 'The control gap may permit unauthorized activity, financial loss, or delayed regulatory response.',
                        'recommendation' => 'Assign a control owner, remediate the identified gap, and retain evidence for independent review.',
                        'process' => $process,
                        'risk_rating' => $riskRating,
                        'status' => $status,
                        'auditee_response' => $hasResponse ? 'The control owner has accepted the observation and is coordinating remediation evidence.' : null,
                        'response_by' => $hasResponse ? $auditee?->id : null,
                        'response_at' => $hasResponse ? now()->subDays(12 - ($sequence % 8)) : null,
                        'closure_requested_by' => $hasClosureRequest ? $auditee?->id : null,
                        'closure_requested_at' => $hasClosureRequest ? now()->subDays(5) : null,
                        'closure_request_notes' => $hasClosureRequest ? 'Resolution evidence submitted for independent validation.' : null,
                        'closure_reviewed_by' => $isReviewed ? $leadAuditor->id : null,
                        'closure_reviewed_at' => $isReviewed ? now()->subDays(2) : null,
                        'closure_review_notes' => $isReviewed ? 'Evidence reviewed against the agreed remediation criteria.' : null,
                        'closure_rejection_reason' => $status === Finding::STATUS_CLOSURE_REJECTED ? 'The sample did not cover the complete review period.' : null,
                        'verified_by' => $isVerified ? $leadAuditor->id : null,
                        'verified_at' => $isVerified ? now()->subDay() : null,
                        'verification_notes' => $isVerified ? 'Operating effectiveness confirmed through follow-up testing.' : null,
                        'closed_by' => $isClosed ? $leadAuditor->id : null,
                        'closed_at' => $isClosed ? now()->subHours(8) : null,
                        'created_by' => $leadAuditor->id,
                        'updated_by' => $leadAuditor->id,
                    ]
                );

                $this->createEvidence($finding, FindingEvidence::TYPE_FINDING, $leadAuditor, 'Testing workpaper supporting the recorded control observation.');

                if ($hasClosureRequest) {
                    $this->createEvidence($finding, FindingEvidence::TYPE_RESOLUTION, $auditee, 'Control remediation evidence submitted by the department owner.');
                }

                $this->createAction($finding, $department, $auditee, $leadAuditor, $status, $sequence);
            }
        }
    }

    private function createEvidence(Finding $finding, string $type, User $uploader, string $description): void
    {
        if ($finding->evidences()->where('type', $type)->exists()) {
            return;
        }

        $filename = $type === FindingEvidence::TYPE_RESOLUTION ? 'resolution-evidence.txt' : 'audit-workpaper.txt';
        $path = 'finding-evidence/'.$finding->uuid.'/'.$filename;
        $contents = $description.PHP_EOL;

        Storage::disk('local')->put($path, $contents);

        $finding->evidences()->create([
            'uuid' => (string) Str::uuid(),
            'type' => $type,
            'uploaded_by' => $uploader->id,
            'original_name' => $filename,
            'path' => $path,
            'disk' => 'local',
            'mime_type' => 'text/plain',
            'size' => strlen($contents),
            'description' => $description,
        ]);
    }

    private function createAction(
        Finding $finding,
        Department $department,
        ?User $auditee,
        User $leadAuditor,
        string $findingStatus,
        int $sequence
    ): void {
        $actionStatus = match ($findingStatus) {
            Finding::STATUS_ACTION_PENDING_APPROVAL => FindingAction::STATUS_PENDING_APPROVAL,
            Finding::STATUS_REMEDIATION_IN_PROGRESS => FindingAction::STATUS_IN_PROGRESS,
            Finding::STATUS_READY_FOR_VERIFICATION,
            Finding::STATUS_CLOSURE_REQUESTED,
            Finding::STATUS_CLOSURE_REJECTED,
            Finding::STATUS_VERIFIED,
            Finding::STATUS_CLOSED => FindingAction::STATUS_COMPLETED,
            default => null,
        };

        if ($actionStatus === null || $finding->actions()->exists()) {
            return;
        }

        $isApproved = $actionStatus !== FindingAction::STATUS_PENDING_APPROVAL;
        $dueDate = $sequence % 6 === 0 ? now()->subDays(4) : now()->addDays(14);

        $finding->actions()->create([
            'uuid' => (string) Str::uuid(),
            'responsible_department_id' => $department->id,
            'responsible_user_id' => $auditee?->id,
            'action' => 'Strengthen the control, complete the corrective change, and retain review evidence.',
            'proposed_due_date' => now()->addDays(10)->toDateString(),
            'agreed_due_date' => $dueDate->toDateString(),
            'status' => $actionStatus,
            'approved_by' => $isApproved ? $leadAuditor->id : null,
            'approved_at' => $isApproved ? now()->subDays(8) : null,
            'approval_comment' => $isApproved ? 'Remediation plan approved for tracked follow-up.' : null,
            'completed_at' => $actionStatus === FindingAction::STATUS_COMPLETED ? now()->subDays(2) : null,
            'created_by' => $auditee?->id ?? $leadAuditor->id,
            'updated_by' => $leadAuditor->id,
        ]);
    }
}
