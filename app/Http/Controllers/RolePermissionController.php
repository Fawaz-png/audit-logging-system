<?php

namespace App\Http\Controllers;

use App\Models\Permission;
use App\Models\Role;
use App\Services\AuditLogger;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response;

class RolePermissionController extends Controller
{
    public function index(Request $request): Response
    {
        abort_unless($request->user()->hasPermission('user.manage'), 403);

        return Inertia::render('Roles/Index', [
            'roles' => Role::query()
                ->with('permissions:id,name,slug')
                ->orderBy('name')
                ->get(['id', 'name', 'slug']),
            'permissions' => Permission::query()
                ->orderBy('name')
                ->get(['id', 'name', 'slug']),
        ]);
    }

    public function update(Request $request, Role $role, AuditLogger $auditLogger): RedirectResponse
    {
        abort_unless($request->user()->hasPermission('user.manage'), 403);
        abort_if($role->slug === 'system-administrator', 403);

        $validated = $request->validate([
            'permissions' => ['present', 'array'],
            'permissions.*' => ['integer', 'distinct', 'exists:permissions,id'],
        ]);

        $permissionIds = array_map('intval', $validated['permissions']);
        $userManagementPermissionId = Permission::query()
            ->where('slug', 'user.manage')
            ->value('id');

        if ($userManagementPermissionId && in_array((int) $userManagementPermissionId, $permissionIds, true)) {
            throw ValidationException::withMessages([
                'permissions' => 'User management access cannot be granted through role editing.',
            ]);
        }

        $before = $role->permissions()->orderBy('slug')->pluck('slug')->all();
        $after = Permission::query()
            ->whereIn('id', $permissionIds)
            ->orderBy('slug')
            ->pluck('slug')
            ->all();

        DB::transaction(function () use ($role, $permissionIds, $auditLogger, $request, $before, $after): void {
            $role->permissions()->sync($permissionIds);
            $auditLogger->record(
                'ROLE_PERMISSIONS_UPDATED',
                $role,
                $request->user(),
                ['permissions' => $before],
                ['permissions' => $after]
            );
        });

        return redirect()
            ->route('roles.index')
            ->with('success', 'Role permissions updated.');
    }
}
