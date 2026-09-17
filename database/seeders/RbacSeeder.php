<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Permission;
use App\Models\Role;

class RbacSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */

    public function run(): void
    {
        $permissions = [
            [
                'name' => 'Create Audit',
                'slug' => 'audit.create',
            ],
            [
                'name' => 'Assign Auditors',
                'slug' => 'audit.assign',
            ],
            [
                'name' => 'View Audit',
                'slug' => 'audit.view',
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
            'lead-auditor' => 'Lead Auditor',
            'supporting-auditor' => 'Supporting Auditor',
            'auditee' => 'Auditee',
        ];

        foreach ($roles as $slug => $name) {
            Role::firstOrCreate(
                ['slug' => $slug],
                ['name' => $name]
            );
        }

        $permissionsBySlug = Permission::all()->keyBy('slug');

        Role::where('slug', 'system-administrator')->first()
            ->permissions()
            ->sync($permissionsBySlug->values());

        Role::where('slug', 'head-of-is-audit')->first()
            ->permissions()
            ->sync([
                $permissionsBySlug['audit.create'],
                $permissionsBySlug['audit.assign'],
                $permissionsBySlug['audit.view'],
                $permissionsBySlug['finding.create'],
                $permissionsBySlug['finding.update'],
                $permissionsBySlug['finding.verify'],
                $permissionsBySlug['finding.close'],
                $permissionsBySlug['audit-log.view'],
            ]);

        Role::where('slug', 'lead-auditor')->first()
            ->permissions()
            ->sync([
                $permissionsBySlug['audit.view'],
                $permissionsBySlug['audit.assign'],
                $permissionsBySlug['finding.create'],
                $permissionsBySlug['finding.update'],
                $permissionsBySlug['finding.verify'],
            ]);

        Role::where('slug', 'supporting-auditor')->first()
            ->permissions()
            ->sync([
                $permissionsBySlug['audit.view'],
                $permissionsBySlug['finding.create'],
                $permissionsBySlug['finding.update'],
            ]);

        Role::where('slug', 'auditee')->first()
            ->permissions()
            ->sync([
                $permissionsBySlug['audit.view'],
                $permissionsBySlug['finding.respond'],
                $permissionsBySlug['finding.remediate'],
            ]);
    }
}
