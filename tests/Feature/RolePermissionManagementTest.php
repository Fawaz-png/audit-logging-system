<?php

namespace Tests\Feature;

use App\Models\Department;
use App\Models\Permission;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class RolePermissionManagementTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->withoutVite();
    }

    public function test_role_permission_matrix_is_limited_to_user_managers(): void
    {
        $manager = $this->createUser('role-manager', ['user.manage']);
        $viewer = $this->createUser('role-viewer');

        $this->actingAs($viewer)
            ->get(route('roles.index'))
            ->assertForbidden();

        $this->actingAs($manager)
            ->get(route('roles.index'))
            ->assertInertia(fn (Assert $page) => $page
                ->component('Roles/Index')
                ->has('roles')
                ->has('permissions')
            );
    }

    public function test_permission_changes_are_audited_and_system_administrator_role_is_locked(): void
    {
        $manager = $this->createUser('role-manager', ['user.manage']);
        $role = Role::create(['name' => 'Control Reviewer', 'slug' => 'control-reviewer']);
        $permission = Permission::create(['name' => 'View Audits', 'slug' => 'audit.view']);
        $restrictedPermission = Permission::where('slug', 'user.manage')->firstOrFail();

        $this->actingAs($manager)
            ->put(route('roles.permissions.update', $role), ['permissions' => [$permission->id]])
            ->assertRedirect(route('roles.index'));

        $this->assertTrue($role->fresh()->permissions()->whereKey($permission->id)->exists());
        $this->assertDatabaseHas('audit_logs', [
            'action' => 'ROLE_PERMISSIONS_UPDATED',
            'auditable_type' => Role::class,
            'auditable_id' => $role->id,
        ]);

        $this->actingAs($manager)
            ->put(route('roles.permissions.update', $role), ['permissions' => [$restrictedPermission->id]])
            ->assertSessionHasErrors('permissions');

        $systemRole = Role::create(['name' => 'System Administrator', 'slug' => 'system-administrator']);

        $this->actingAs($manager)
            ->put(route('roles.permissions.update', $systemRole), ['permissions' => []])
            ->assertForbidden();
    }

    /** @param array<int, string> $permissionSlugs */
    private function createUser(string $slug, array $permissionSlugs = []): User
    {
        $department = Department::create([
            'name' => 'Department '.$slug,
            'code' => Str::upper(Str::random(5)),
        ]);
        $role = Role::create([
            'name' => Str::headline($slug),
            'slug' => $slug,
        ]);

        foreach ($permissionSlugs as $permissionSlug) {
            $permission = Permission::firstOrCreate(
                ['slug' => $permissionSlug],
                ['name' => Str::headline($permissionSlug)]
            );
            $role->permissions()->attach($permission);
        }

        return User::factory()->create([
            'role_id' => $role->id,
            'department_id' => $department->id,
        ]);
    }
}
