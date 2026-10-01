<?php

namespace Tests\Feature;

use App\Models\AuditLog;
use App\Models\Department;
use App\Models\Permission;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class AuditLogCenterTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->withoutVite();
    }

    public function test_audit_log_center_is_permission_gated_and_searches_records(): void
    {
        $user = $this->createUser('audit-log-reader', true);
        $otherUser = $this->createUser('audit-log-no-access');

        AuditLog::query()->create([
            'uuid' => (string) Str::uuid(),
            'user_id' => $user->id,
            'action' => 'FINDING_UPDATED',
            'auditable_type' => 'App\\Models\\Finding',
            'auditable_id' => 10,
            'new_payload' => ['reference' => 'FND-FIN-001', 'title' => 'Privileged access review'],
            'created_at' => now(),
        ]);

        AuditLog::query()->create([
            'uuid' => (string) Str::uuid(),
            'user_id' => $user->id,
            'action' => 'AUDIT_CREATED',
            'auditable_type' => 'App\\Models\\Audit',
            'auditable_id' => 20,
            'new_payload' => ['reference' => 'AUD-OPS-001', 'title' => 'Operations resilience review'],
            'created_at' => now()->subMinute(),
        ]);

        $this->actingAs($otherUser)
            ->get(route('audit-logs.index'))
            ->assertForbidden();

        $this->actingAs($user)
            ->get(route('audit-logs.index', ['search' => 'FND-FIN-001']))
            ->assertInertia(fn (Assert $page) => $page
                ->component('AuditLogs/Index')
                ->where('filters.search', 'FND-FIN-001')
                ->has('logs.data', 1)
                ->where('logs.data.0.action', 'FINDING_UPDATED')
                ->missing('logs.data.0.old_payload')
                ->missing('logs.data.0.new_payload')
                ->missing('logs.data.0.ip_address')
                ->missing('logs.data.0.user_agent')
            );
    }

    private function createUser(string $slug, bool $canViewLogs = false): User
    {
        $department = Department::create([
            'name' => 'Department '.$slug,
            'code' => Str::upper(Str::random(5)),
        ]);
        $role = Role::create([
            'name' => Str::headline($slug),
            'slug' => $slug,
        ]);

        if ($canViewLogs) {
            $permission = Permission::firstOrCreate(
                ['slug' => 'audit-log.view'],
                ['name' => 'View Audit Logs']
            );
            $role->permissions()->attach($permission);
        }

        return User::factory()->create([
            'role_id' => $role->id,
            'department_id' => $department->id,
        ]);
    }
}
