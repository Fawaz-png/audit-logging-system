<?php

namespace Database\Seeders;

use App\Models\Department;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class DepartmentSeeder extends Seeder
{
    public function run(): void
    {
        $departments = [
            [
                'name' => 'Information Technology',
                'code' => 'IT',
            ],
            [
                'name' => 'Finance',
                'code' => 'FIN',
            ],
            [
                'name' => 'Operations',
                'code' => 'OPS',
            ],
            [
                'name' => 'Human Resources',
                'code' => 'HR',
            ],
        ];

        foreach ($departments as $department) {
            Department::create([
                'uuid' => (string) Str::uuid(),
                'name' => $department['name'],
                'code' => $department['code'],
            ]);
        }
    }
}