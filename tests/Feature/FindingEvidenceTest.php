<?php

namespace Tests\Feature;

use App\Models\Audit;
use App\Models\Department;
use App\Models\Finding;
use App\Models\FindingAction;
use App\Models\FindingEvidence;
use App\Models\Permission;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Tests\TestCase;

class FindingEvidenceTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->withoutVite();
    }

    public function test_auditee_submits_closure_request_with_resolution_evidence_for_auditor_approval(): void
    {
        Storage::fake('local');

        [$finding, $auditee, $leadAuditor] = $this->createReadyFinding();
        $this->assertTrue($auditee->can('requestClosure', $finding));

        $this->actingAs($auditee);

        $uploadResponse = $this->post(route('findings.closure-requests.store', $finding), [
            'notes' => 'Remediation is complete and ready for review.',
            'description' => 'Configuration after remediation.',
            'file' => UploadedFile::fake()->createWithContent(
                'remediation.txt',
                'Updated configuration'
            ),
        ]);

        $uploadResponse->assertRedirect(route('findings.show', $finding));

        $finding->refresh();
        $evidence = $finding->evidences()->firstOrFail();
        $this->assertSame(FindingEvidence::TYPE_RESOLUTION, $evidence->type);
        $this->assertSame('Configuration after remediation.', $evidence->description);
        $this->assertSame('remediation.txt', $evidence->original_name);
        $this->assertSame('local', $evidence->disk);
        $this->assertSame(strlen('Updated configuration'), $evidence->size);
        $this->assertTrue(Storage::disk('local')->exists($evidence->path));
        $this->assertSame(Finding::STATUS_CLOSURE_REQUESTED, $finding->status);
        $this->assertSame($auditee->id, $finding->closure_requested_by);

        $this->actingAs($leadAuditor)
            ->get(route('findings.show', $finding))
            ->assertInertia(fn ($page) => $page
                ->where('finding.evidences.0.type', 'RESOLUTION')
                ->where('finding.evidences.0.description', 'Configuration after remediation.')
                ->where('finding.evidences.0.uploader.name', $auditee->name)
            );

        $this->actingAs($leadAuditor)
            ->get(route('findings.evidences.download', [$finding, $evidence]))
            ->assertDownload('remediation.txt');

        $this->actingAs($leadAuditor)
            ->post(route('findings.closure-requests.approve', $finding), [
                'notes' => 'Evidence confirms remediation.',
            ])
            ->assertRedirect(route('findings.show', $finding));

        $this->assertDatabaseHas('findings', [
            'id' => $finding->id,
            'status' => Finding::STATUS_CLOSED,
            'closed_by' => $leadAuditor->id,
            'closure_reviewed_by' => $leadAuditor->id,
        ]);
        $this->assertDatabaseHas('audit_logs', [
            'action' => 'FINDING_CLOSURE_REQUESTED',
            'auditable_type' => Finding::class,
            'auditable_id' => $finding->id,
            'user_id' => $auditee->id,
        ]);
        $this->assertDatabaseHas('audit_logs', [
            'action' => 'FINDING_CLOSURE_APPROVED',
            'auditable_type' => Finding::class,
            'auditable_id' => $finding->id,
            'user_id' => $leadAuditor->id,
        ]);
    }

    public function test_closure_request_requires_auditee_authority_and_valid_evidence(): void
    {
        Storage::fake('local');

        [$finding, , $leadAuditor, $outsider] = $this->createReadyFinding();

        $this->actingAs($outsider)
            ->post(route('findings.closure-requests.store', $finding), [
                'notes' => 'Remediation is complete.',
                'file' => UploadedFile::fake()->createWithContent('proof.txt', 'Evidence'),
            ])
            ->assertForbidden();

        $this->assertDatabaseCount('finding_evidences', 0);

        $this->actingAs($leadAuditor)
            ->post(route('findings.closure-requests.store', $finding), [
                'notes' => 'Remediation is complete.',
                'file' => UploadedFile::fake()->createWithContent('proof.txt', 'Evidence'),
            ])
            ->assertForbidden();

        $this->assertDatabaseCount('finding_evidences', 0);

        [$otherFinding, $auditee] = $this->createReadyFinding('FND-OTHER-001');

        $this->actingAs($auditee)
            ->post(route('findings.closure-requests.store', $otherFinding), [
                'notes' => 'Remediation is complete.',
                'file' => UploadedFile::fake()->create('payload.exe', 10, 'application/octet-stream'),
            ])
            ->assertSessionHasErrors('file');

        $this->assertDatabaseCount('finding_evidences', 0);
    }

    public function test_evidence_download_does_not_allow_cross_finding_access(): void
    {
        Storage::fake('local');

        [$finding, $auditee] = $this->createReadyFinding();
        [$otherFinding] = $this->createReadyFinding('FND-OTHER-002');

        $this->actingAs($auditee)
            ->post(route('findings.closure-requests.store', $finding), [
                'notes' => 'Remediation is complete.',
                'file' => UploadedFile::fake()->createWithContent('proof.txt', 'Evidence'),
            ])
            ->assertRedirect();

        $evidence = $finding->evidences()->firstOrFail();

        $this->actingAs($auditee)
            ->get(route('findings.evidences.download', [$otherFinding, $evidence]))
            ->assertNotFound();
    }

    public function test_finding_registration_requires_and_stores_finding_evidence(): void
    {
        Storage::fake('local');

        [$finding, , $auditor] = $this->createReadyFinding('FND-REGISTRATION-001');

        $this->actingAs($auditor)
            ->post(route('findings.store', $finding->audit), [
                'reference' => 'FND-REGISTRATION-002',
                'title' => 'Missing access review evidence',
                'description' => 'No evidence of a periodic access review.',
                'criteria' => 'Access must be reviewed periodically.',
                'effect' => 'Excessive access may remain undetected.',
                'recommendation' => 'Establish periodic access reviews.',
                'process' => 'Access management',
                'risk_rating' => 'HIGH',
                'evidence_description' => 'Workpaper documenting sampled user access.',
                'evidence_file' => UploadedFile::fake()->createWithContent('access-review.txt', 'Sample results'),
            ])
            ->assertRedirect();

        $registeredFinding = Finding::query()
            ->where('reference', 'FND-REGISTRATION-002')
            ->firstOrFail();
        $evidence = $registeredFinding->evidences()->firstOrFail();

        $this->assertSame(FindingEvidence::TYPE_FINDING, $evidence->type);
        $this->assertSame('Workpaper documenting sampled user access.', $evidence->description);
        $this->assertTrue(Storage::disk('local')->exists($evidence->path));
    }

    public function test_finding_registration_is_rejected_until_audit_is_concluded(): void
    {
        Storage::fake('local');

        [$finding, , $auditor] = $this->createReadyFinding('FND-REGISTRATION-003');
        $finding->audit->update(['status' => 'IN_PROGRESS']);

        $this->actingAs($auditor)
            ->post(route('findings.store', $finding->audit), [
                'reference' => 'FND-REGISTRATION-004',
                'title' => 'Finding before exit meeting',
                'description' => 'Condition.',
                'criteria' => 'Criteria.',
                'effect' => 'Effect.',
                'recommendation' => 'Recommendation.',
                'process' => 'Access management',
                'risk_rating' => 'MEDIUM',
                'evidence_file' => UploadedFile::fake()->createWithContent('proof.txt', 'Evidence'),
            ])
            ->assertSessionHasErrors('audit');

        $this->assertDatabaseMissing('findings', ['reference' => 'FND-REGISTRATION-004']);
        Storage::disk('local')->assertDirectoryEmpty('finding-evidence');
    }

    public function test_auditor_action_approval_starts_remediation_sla_immediately(): void
    {
        Storage::fake('local');

        [$finding, $auditee, $leadAuditor] = $this->createReadyFinding('FND-SLA-001');
        $finding->update(['status' => Finding::STATUS_RESPONSE_RECEIVED]);
        $finding->actions()->delete();

        $this->actingAs($auditee)
            ->post(route('findings.actions.store', $finding), [
                'responsible_department_id' => $auditee->department_id,
                'responsible_user_id' => $auditee->id,
                'action' => 'Remove stale privileged access.',
                'proposed_due_date' => today()->addDays(20)->toDateString(),
            ])
            ->assertRedirect();

        $action = $finding->actions()->firstOrFail();

        $this->actingAs($leadAuditor)
            ->post(route('finding-actions.approve', $action), [
                'agreed_due_date' => today()->addDays(15)->toDateString(),
                'comment' => 'Agreed during the discussion.',
            ])
            ->assertRedirect();

        $action->refresh();
        $this->assertSame(FindingAction::STATUS_IN_PROGRESS, $action->status);
        $this->assertSame(today()->addDays(15)->toDateString(), $action->agreed_due_date->toDateString());
        $this->assertSame(Finding::STATUS_REMEDIATION_IN_PROGRESS, $finding->fresh()->status);
        $this->assertDatabaseHas('audit_logs', [
            'action' => 'CORRECTIVE_ACTION_APPROVED_SLA_STARTED',
            'auditable_id' => $action->id,
            'user_id' => $leadAuditor->id,
        ]);
    }

    /** @return array{Finding, User, User, User} */
    private function createReadyFinding(string $reference = 'FND-001'): array
    {
        $department = Department::create([
            'name' => 'Finance '.Str::random(5),
            'code' => Str::upper(Str::random(3)),
        ]);
        $auditor = $this->createUser('auditor', $department, [
            'audit.view',
            'finding.update',
            'finding.create',
            'finding.verify',
            'finding.close',
        ]);
        $auditee = $this->createUser('auditee', $department, [
            'audit.view',
            'finding.remediate',
            'finding.respond',
        ]);
        $outsider = $this->createUser('observer', $department, [
            'audit.view',
            'finding.update',
        ]);
        $audit = Audit::create([
            'reference' => 'AUD-'.Str::upper(Str::random(8)),
            'title' => 'Finance audit',
            'objective' => 'Test objective.',
            'scope' => 'Test scope.',
            'department_id' => $department->id,
            'audit_mode' => 'ONSITE',
            'methodology' => 'RISK_BASED',
            'start_date' => '2026-10-01',
            'status' => 'COMPLETED',
            'created_by' => $auditor->id,
        ]);
        $audit->auditors()->attach($auditor, [
            'uuid' => (string) Str::uuid(),
            'assignment_type' => 'LEAD_AUDITOR',
        ]);
        $audit->auditors()->attach($auditee, [
            'uuid' => (string) Str::uuid(),
            'assignment_type' => 'AUDITEE',
        ]);

        $finding = Finding::create([
            'audit_id' => $audit->id,
            'reference' => $reference,
            'title' => 'Access review finding',
            'description' => 'Observed control weakness.',
            'criteria' => 'Control criteria.',
            'effect' => 'Potential impact.',
            'recommendation' => 'Remediate the weakness.',
            'process' => 'Access management',
            'risk_rating' => 'MEDIUM',
            'status' => Finding::STATUS_READY_FOR_VERIFICATION,
            'created_by' => $auditor->id,
            'updated_by' => $auditor->id,
        ]);
        $finding->actions()->create([
            'responsible_department_id' => $department->id,
            'responsible_user_id' => $auditee->id,
            'action' => 'Update access configuration.',
            'proposed_due_date' => '2026-10-01',
            'agreed_due_date' => '2026-10-01',
            'status' => FindingAction::STATUS_COMPLETED,
            'completed_at' => now(),
            'created_by' => $auditee->id,
            'updated_by' => $auditee->id,
        ]);

        return [$finding, $auditee, $auditor, $outsider];
    }

    /** @param array<int, string> $permissionSlugs */
    private function createUser(string $roleSlug, Department $department, array $permissionSlugs): User
    {
        $role = Role::firstOrCreate(
            ['slug' => $roleSlug],
            ['name' => Str::headline($roleSlug)]
        );

        foreach ($permissionSlugs as $permissionSlug) {
            $permission = Permission::firstOrCreate(
                ['slug' => $permissionSlug],
                ['name' => Str::headline($permissionSlug)]
            );
            $role->permissions()->syncWithoutDetaching([$permission->id]);
        }

        return User::factory()->create([
            'role_id' => $role->id,
            'department_id' => $department->id,
        ]);
    }
}
