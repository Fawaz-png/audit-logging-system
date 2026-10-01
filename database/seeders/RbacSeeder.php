<?php

namespace Database\Seeders;

use App\Models\Permission;
use App\Models\Role;
use Illuminate\Database\Seeder;

class RbacSeeder extends Seeder
{
    public function run(): void
    {
        $permissions = [
            [
                'name' => 'View Audits',
                'slug' => 'audit.view',
            ],
            [
                'name' => 'Create Audit',
                'slug' => 'audit.create',
            ],
            [
                'name' => 'Update Audit',
                'slug' => 'audit.update',
            ],

            [
                'name' => 'Create Finding',
                'slug' => 'finding.create',
            ],
            [
                'name' => 'Update Finding',
                'slug' => 'finding.update',
            ],
            [
                'name' => 'Respond to Finding',
                'slug' => 'finding.respond',
            ],
            [
                'name' => 'Submit Remediation',
                'slug' => 'finding.remediate',
            ],
            [
                'name' => 'Verify Finding',
                'slug' => 'finding.verify',
            ],
            [
                'name' => 'Close Finding',
                'slug' => 'finding.close',
            ],
            [
                'name' => 'Delete Finding',
                'slug' => 'finding.delete',
            ],

            [
                'name' => 'View Audit Logs',
                'slug' => 'audit-log.view',
            ],

            [
                'name' => 'Manage Users',
                'slug' => 'user.manage',
            ],
        ];

        foreach ($permissions as $permission) {
            Permission::firstOrCreate(
                ['slug' => $permission['slug']],
                ['name' => $permission['name']]
            );
        }

        $roles = [
            'system-administrator' => 'System Administrator',
            'head-of-is-audit' => 'Head of IS Audit',
            'auditor' => 'Auditor',
            'auditee' => 'Auditee',
        ];

        foreach ($roles as $slug => $name) {
            Role::firstOrCreate(
                ['slug' => $slug],
                ['name' => $name]
            );
        }

        $permissionsBySlug = Permission::all()->keyBy('slug');

        Role::where('slug', 'system-administrator')
            ->first()
            ->permissions()
            ->sync($permissionsBySlug->values());

        Role::where('slug', 'head-of-is-audit')
            ->first()
            ->permissions()
            ->sync([
                $permissionsBySlug['audit.view'],
                $permissionsBySlug['audit.create'],
                $permissionsBySlug['audit.update'],

                $permissionsBySlug['finding.create'],
                $permissionsBySlug['finding.update'],
                $permissionsBySlug['finding.verify'],
                $permissionsBySlug['finding.close'],
                $permissionsBySlug['finding.delete'],

                $permissionsBySlug['audit-log.view'],
            ]);

        Role::where('slug', 'auditor')
            ->first()
            ->permissions()
            ->sync([
                $permissionsBySlug['audit.view'],
                $permissionsBySlug['audit.create'],
                $permissionsBySlug['audit.update'],

                $permissionsBySlug['finding.create'],
                $permissionsBySlug['finding.update'],
                $permissionsBySlug['finding.verify'],
                $permissionsBySlug['finding.close'],
            ]);

        Role::where('slug', 'auditee')
            ->first()
            ->permissions()
            ->sync([
                $permissionsBySlug['audit.view'],
                $permissionsBySlug['finding.respond'],
                $permissionsBySlug['finding.remediate'],
            ]);
    }
}
