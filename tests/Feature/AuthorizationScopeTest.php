<?php

namespace Tests\Feature;

use App\Models\Audit;
use App\Models\Department;
use App\Models\Finding;
use App\Models\Permission;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class AuthorizationScopeTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->withoutVite();
    }

    public function test_auditee_audit_index_contains_all_audits_in_their_department(): void
    {
        $department = $this->createDepartment('Finance');
        $otherDepartment = $this->createDepartment('Operations');
        $auditee = $this->createUser('auditee', $department, ['audit.view']);

        $assignedAudit = $this->createAudit($department, 'AUD-FIN-001');
        $unassignedAudit = $this->createAudit($department, 'AUD-FIN-002');
        $otherDepartmentAudit = $this->createAudit($otherDepartment, 'AUD-OPS-001');
        $differentAssignmentAudit = $this->createAudit($department, 'AUD-FIN-003');

        $this->assignToAudit($assignedAudit, $auditee, 'AUDITEE');
        $this->assignToAudit($otherDepartmentAudit, $auditee, 'AUDITEE');
        $this->assignToAudit($differentAssignmentAudit, $auditee, 'SUPPORTING_AUDITOR');

        $response = $this->actingAs($auditee)
            ->get(route('audits.index'))
            ->assertInertia(fn (Assert $page) => $page
                ->component('Audits/Index')
                ->has('audits', 3)
            );

        $visibleAuditIds = collect($response->inertiaProps('audits'))
            ->pluck('id')
            ->all();
        $expectedAuditIds = [$assignedAudit->id, $unassignedAudit->id, $differentAssignmentAudit->id];
        sort($visibleAuditIds);
        sort($expectedAuditIds);

        $this->assertSame($expectedAuditIds, $visibleAuditIds);
    }

    public function test_auditor_can_view_the_full_audit_portfolio(): void
    {
        $firstDepartment = $this->createDepartment('Finance');
        $secondDepartment = $this->createDepartment('Operations');
        $auditor = $this->createUser('auditor', $firstDepartment, ['audit.view']);
        $firstAudit = $this->createAudit($firstDepartment, 'AUD-FIN-001');
        $secondAudit = $this->createAudit($secondDepartment, 'AUD-OPS-001');

        $response = $this->actingAs($auditor)
            ->get(route('audits.index'))
            ->assertInertia(fn (Assert $page) => $page
                ->component('Audits/Index')
                ->has('audits', 2)
            );

        $visibleAuditIds = collect($response->inertiaProps('audits'))
            ->pluck('id')
            ->all();
        $expectedAuditIds = [$firstAudit->id, $secondAudit->id];
        sort($visibleAuditIds);
        sort($expectedAuditIds);

        $this->assertSame($expectedAuditIds, $visibleAuditIds);

        $this->get(route('audits.show', $secondAudit))
            ->assertInertia(fn (Assert $page) => $page
                ->component('Audits/Show')
                ->where('audit.id', $secondAudit->id)
            );
    }

    public function test_unrecognized_role_cannot_view_the_audit_portfolio(): void
    {
        $department = $this->createDepartment('Finance');
        $user = $this->createUser('compliance-observer', $department, ['audit.view']);

        $this->actingAs($user)
            ->get(route('audits.index'))
            ->assertForbidden();
    }

    public function test_auditee_can_open_unassigned_department_audit_but_not_cross_department_audit(): void
    {
        $department = $this->createDepartment('Finance');
        $otherDepartment = $this->createDepartment('Operations');
        $auditee = $this->createUser('auditee', $department, ['audit.view']);
        $unassignedAudit = $this->createAudit($department, 'AUD-FIN-001');
        $crossDepartmentAudit = $this->createAudit($otherDepartment, 'AUD-OPS-001');
        $unassignedFinding = $this->createFinding($unassignedAudit, 'FND-FIN-001');

        $this->assignToAudit($crossDepartmentAudit, $auditee, 'AUDITEE');

        $this->actingAs($auditee)
            ->get(route('audits.show', $unassignedAudit))
            ->assertInertia(fn (Assert $page) => $page
                ->component('Audits/Show')
                ->where('audit.id', $unassignedAudit->id)
            );

        $this->get(route('audits.show', $crossDepartmentAudit))
            ->assertNotFound();

        $this->get(route('findings.show', $unassignedFinding))
            ->assertInertia(fn (Assert $page) => $page
                ->component('Findings/Show')
                ->where('finding.id', $unassignedFinding->id)
            );
    }

    public function test_finding_register_contains_department_findings_without_audit_assignment(): void
    {
        $department = $this->createDepartment('Finance');
        $otherDepartment = $this->createDepartment('Operations');
        $auditee = $this->createUser('auditee', $department, ['audit.view']);
        $departmentAudit = $this->createAudit($department, 'AUD-FIN-001');
        $otherAudit = $this->createAudit($otherDepartment, 'AUD-OPS-001');
        $departmentFinding = $this->createFinding($departmentAudit, 'FND-FIN-001');
        $this->createFinding($otherAudit, 'FND-OPS-001');

        $this->actingAs($auditee)
            ->get(route('findings.index'))
            ->assertInertia(fn (Assert $page) => $page
                ->component('Findings/Index')
                ->where('findings.total', 1)
                ->where('findings.data.0.id', $departmentFinding->id)
            );
    }

    public function test_auditee_cannot_open_a_finding_outside_their_audit_scope(): void
    {
        $department = $this->createDepartment('Finance');
        $otherDepartment = $this->createDepartment('Operations');
        $auditee = $this->createUser('auditee', $department, ['audit.view']);
        $audit = $this->createAudit($otherDepartment, 'AUD-OPS-001');
        $finding = $this->createFinding($audit, 'FND-OPS-001');

        $this->actingAs($auditee)
            ->get(route('findings.show', $finding))
            ->assertNotFound();
    }

    public function test_user_index_forbids_users_without_user_management_permission(): void
    {
        $department = $this->createDepartment('Finance');
        $auditee = $this->createUser('auditee', $department, ['audit.view']);

        $this->actingAs($auditee)
            ->get(route('users.index'))
            ->assertForbidden();
    }

    public function test_user_index_is_available_to_users_with_user_management_permission(): void
    {
        $department = $this->createDepartment('Finance');
        $manager = $this->createUser('system-administrator', $department, ['user.manage']);

        $this->actingAs($manager)
            ->get(route('users.index'))
            ->assertInertia(fn (Assert $page) => $page
                ->component('Users/Index')
                ->has('users', 1)
            );
    }

    private function createDepartment(string $name): Department
    {
        return Department::create([
            'name' => $name,
            'code' => Str::upper(Str::substr($name, 0, 3)),
        ]);
    }

    /** @param array<int, string> $permissionSlugs */
    private function createUser(string $roleSlug, Department $department, array $permissionSlugs): User
    {
        $role = Role::create([
            'name' => Str::headline($roleSlug),
            'slug' => $roleSlug,
        ]);

        foreach ($permissionSlugs as $slug) {
            $permission = Permission::firstOrCreate(
                ['slug' => $slug],
                ['name' => Str::headline($slug)]
            );

            $role->permissions()->attach($permission);
        }

        return User::factory()->create([
            'role_id' => $role->id,
            'department_id' => $department->id,
        ]);
    }

    private function createAudit(Department $department, string $reference): Audit
    {
        $creatorRole = Role::firstOrCreate(
            ['slug' => 'audit-test-creator'],
            ['name' => 'Audit Test Creator']
        );
        $creator = User::factory()->create([
            'role_id' => $creatorRole->id,
            'department_id' => $department->id,
        ]);

        return Audit::create([
            'reference' => $reference,
            'title' => "Audit {$reference}",
            'objective' => 'Review control effectiveness.',
            'scope' => 'Review selected controls.',
            'department_id' => $department->id,
            'audit_mode' => 'ONSITE',
            'methodology' => 'RISK_BASED',
            'start_date' => '2026-10-01',
            'status' => 'PLANNED',
            'created_by' => $creator->id,
        ]);
    }

    private function assignToAudit(Audit $audit, User $user, string $assignmentType): void
    {
        $audit->auditors()->attach($user, [
            'uuid' => (string) Str::uuid(),
            'assignment_type' => $assignmentType,
        ]);
    }

    private function createFinding(Audit $audit, string $reference): Finding
    {
        return Finding::create([
            'audit_id' => $audit->id,
            'reference' => $reference,
            'title' => "Finding {$reference}",
            'description' => 'Observed control weakness.',
            'criteria' => 'Control criteria.',
            'effect' => 'Potential impact.',
            'recommendation' => 'Remediate the weakness.',
            'process' => 'Access management',
            'risk_rating' => 'MEDIUM',
            'status' => 'OPEN',
            'created_by' => $audit->created_by,
            'updated_by' => $audit->created_by,
        ]);
    }
}
