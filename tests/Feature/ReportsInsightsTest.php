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

class ReportsInsightsTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->withoutVite();
    }

    public function test_report_insights_only_include_audits_visible_to_the_user(): void
    {
        $finance = $this->createDepartment('Finance');
        $operations = $this->createDepartment('Operations');
        $user = $this->createUser($finance, true);
        $financeAudit = $this->createAudit($finance, $user, 'AUD-FIN-001');
        $operationsAudit = $this->createAudit($operations, $user, 'AUD-OPS-001');
        $this->createFinding($financeAudit, $user, 'FND-FIN-001', Finding::STATUS_OPEN, 'CRITICAL');
        $this->createFinding($operationsAudit, $user, 'FND-OPS-001', Finding::STATUS_CLOSED, 'LOW');

        $this->actingAs($user)
            ->get(route('reports.index'))
            ->assertInertia(fn (Assert $page) => $page
                ->component('Reports/Index')
                ->where('summary.audit_count', 1)
                ->where('summary.open_findings', 1)
                ->where('summary.high_risk_findings', 1)
                ->has('departmentExposure', 1)
                ->where('departmentExposure.0.department_name', 'Finance')
            );

        $withoutPermission = $this->createUser($finance, false);

        $this->actingAs($withoutPermission)
            ->get(route('reports.index'))
            ->assertForbidden();
    }

    private function createDepartment(string $name): Department
    {
        return Department::create([
            'name' => $name,
            'code' => Str::upper(Str::random(5)),
        ]);
    }

    private function createUser(Department $department, bool $canViewAudits): User
    {
        $role = Role::create([
            'name' => $canViewAudits ? 'Report Viewer' : 'No Report Access',
            'slug' => $canViewAudits ? 'auditee' : 'report-denied',
        ]);

        if ($canViewAudits) {
            $permission = Permission::create(['name' => 'View Audits', 'slug' => 'audit.view']);
            $role->permissions()->attach($permission);
        }

        return User::factory()->create([
            'role_id' => $role->id,
            'department_id' => $department->id,
        ]);
    }

    private function createAudit(Department $department, User $creator, string $reference): Audit
    {
        return Audit::create([
            'reference' => $reference,
            'title' => $reference,
            'objective' => 'Review operating effectiveness.',
            'scope' => 'Review key department controls.',
            'department_id' => $department->id,
            'audit_mode' => 'REMOTE',
            'methodology' => 'RISK_BASED',
            'start_date' => now()->toDateString(),
            'status' => 'IN_PROGRESS',
            'created_by' => $creator->id,
        ]);
    }

    private function createFinding(Audit $audit, User $creator, string $reference, string $status, string $risk): void
    {
        Finding::create([
            'audit_id' => $audit->id,
            'reference' => $reference,
            'title' => $reference,
            'description' => 'Finding details.',
            'criteria' => 'Control criteria.',
            'effect' => 'Potential impact.',
            'recommendation' => 'Address the control weakness.',
            'process' => 'Control operation',
            'risk_rating' => $risk,
            'status' => $status,
            'created_by' => $creator->id,
        ]);
    }
}
