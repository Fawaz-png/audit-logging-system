<?php

namespace Database\Seeders;

use App\Models\Department;
use App\Models\Role;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class UserSeeder extends Seeder
{
    public function run(): void
    {
        $it = Department::where('code', 'IT')->firstOrFail();
        $finance = Department::where('code', 'FIN')->firstOrFail();

        $users = [
            [
                'name' => 'System Administrator',
                'email' => 'admin@example.com',
                'role' => 'system-administrator',
                'department_id' => $it->id,
            ],
            [
                'name' => 'Head of IS Audit',
                'email' => 'head.audit@example.com',
                'role' => 'head-of-is-audit',
                'department_id' => $it->id,
            ],
            [
                'name' => 'Lead Auditor',
                'email' => 'lead.auditor@example.com',
                'role' => 'lead-auditor',
                'department_id' => $it->id,
            ],
            [
                'name' => 'Supporting Auditor',
                'email' => 'supporting.auditor@example.com',
                'role' => 'supporting-auditor',
                'department_id' => $it->id,
            ],
            [
                'name' => 'Finance Auditee',
                'email' => 'finance.auditee@example.com',
                'role' => 'auditee',
                'department_id' => $finance->id,
            ],
        ];

        foreach ($users as $data) {
            $role = Role::where('slug', $data['role'])->firstOrFail();

            User::create([
                'uuid' => (string) Str::uuid(),
                'name' => $data['name'],
                'email' => $data['email'],
                'password' => Hash::make('password'),
                'role_id' => $role->id,
                'department_id' => $data['department_id'],
                'active' => true,
            ]);
        }
    }
}