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
            ['name' => 'Information Technology', 'code' => 'IT'],
            ['name' => 'Finance', 'code' => 'FIN'],
            ['name' => 'Operations', 'code' => 'OPS'],
            ['name' => 'Human Resources', 'code' => 'HR'],
            ['name' => 'Corporate Banking', 'code' => 'CBK'],
            ['name' => 'Treasury', 'code' => 'TRS'],
            ['name' => 'Risk & Compliance', 'code' => 'RCO'],
            ['name' => 'Retail Banking', 'code' => 'RBT'],
            ['name' => 'Enterprise Services', 'code' => 'ENT'],
            ['name' => 'Internal Audit', 'code' => 'AUD'],
            ['name' => 'Information Security', 'code' => 'SEC'],
            ['name' => 'Digital Channels', 'code' => 'DCH'],
            ['name' => 'Lending & Credit', 'code' => 'LND'],
            ['name' => 'Payments', 'code' => 'PAY'],
            ['name' => 'Procurement & Vendor Management', 'code' => 'PRC'],
            ['name' => 'Legal & Corporate Secretariat', 'code' => 'LEG'],
        ];

        foreach ($departments as $department) {
            Department::firstOrCreate(
                ['code' => $department['code']],
                [
                    'uuid' => (string) Str::uuid(),
                    'name' => $department['name'],
                ]
            );
        }
    }
}
