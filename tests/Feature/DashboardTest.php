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

class DashboardTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->withoutVite();
    }

    public function test_dashboard_reports_live_stats_for_only_audits_visible_to_the_user(): void
    {
        $finance = $this->createDepartment('Finance');
        $operations = $this->createDepartment('Operations');
        $auditee = $this->createUser('auditee', $finance, ['audit.view']);

        $financeAudit = $this->createAudit($finance, 'AUD-FIN-001');
        $operationsAudit = $this->createAudit($operations, 'AUD-OPS-001');
        $this->createFinding($financeAudit, 'FND-FIN-001', Finding::STATUS_OPEN, 'HIGH');
        $this->createFinding($financeAudit, 'FND-FIN-002', Finding::STATUS_CLOSURE_REQUESTED, 'MEDIUM');
        $this->createFinding($operationsAudit, 'FND-OPS-001', Finding::STATUS_OPEN, 'CRITICAL');

        $this->actingAs($auditee)
            ->get(route('dashboard'))
            ->assertInertia(fn (Assert $page) => $page
                ->component('Dashboard')
                ->where('dashboardScope', 'department')
                ->where('stats.activeAudits', 1)
                ->where('stats.openFindings', 2)
                ->where('stats.pendingResponses', 1)
                ->where('stats.pendingClosureRequests', 1)
                ->where('stats.highRiskFindings', 1)
                ->has('recentFindings', 2)
            );
    }

    private function createDepartment(string $name): Department
    {
        return Department::create([
            'name' => $name,
            'code' => Str::upper(Str::random(3)),
        ]);
    }

    /** @param array<int, string> $permissions */
    private function createUser(string $slug, Department $department, array $permissions): User
    {
        $role = Role::firstOrCreate(
            ['slug' => $slug],
            ['name' => Str::headline($slug)]
        );

        foreach ($permissions as $permissionSlug) {
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

    private function createAudit(Department $department, string $reference): Audit
    {
        $creatorRole = Role::firstOrCreate(
            ['slug' => 'dashboard-test-creator'],
            ['name' => 'Dashboard Test Creator']
        );
        $creator = User::factory()->create([
            'role_id' => $creatorRole->id,
            'department_id' => $department->id,
        ]);

        return Audit::create([
            'reference' => $reference,
            'title' => $reference,
            'objective' => 'Test objective.',
            'scope' => 'Test scope.',
            'department_id' => $department->id,
            'audit_mode' => 'ONSITE',
            'methodology' => 'RISK_BASED',
            'start_date' => '2026-10-01',
            'status' => 'IN_PROGRESS',
            'created_by' => $creator->id,
        ]);
    }

    private function createFinding(Audit $audit, string $reference, string $status, string $risk): Finding
    {
        return Finding::create([
            'audit_id' => $audit->id,
            'reference' => $reference,
            'title' => $reference,
            'description' => 'Finding details.',
            'criteria' => 'Control criteria.',
            'effect' => 'Potential impact.',
            'recommendation' => 'Address the weakness.',
            'process' => 'Access management',
            'risk_rating' => $risk,
            'status' => $status,
            'created_by' => $audit->created_by,
        ]);
    }
}
