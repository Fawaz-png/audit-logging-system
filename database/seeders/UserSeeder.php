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
        $departments = Department::query()->pluck('id', 'code');

        $users = [
            ['name' => 'System Administrator', 'email' => 'admin@example.com', 'role' => 'system-administrator', 'department' => 'IT'],
            ['name' => 'Head of Internal Audit', 'email' => 'head.audit@example.com', 'role' => 'head-of-is-audit', 'department' => 'AUD'],
            ['name' => 'Lead Auditor', 'email' => 'lead.auditor@example.com', 'role' => 'auditor', 'department' => 'AUD'],
            ['name' => 'Supporting Auditor', 'email' => 'supporting.auditor@example.com', 'role' => 'auditor', 'department' => 'AUD'],
            ['name' => 'Audit Operations Analyst', 'email' => 'audit.ops@example.com', 'role' => 'auditor', 'department' => 'AUD'],
            ['name' => 'Data Assurance Lead', 'email' => 'data.assurance@example.com', 'role' => 'auditor', 'department' => 'AUD'],
            ['name' => 'Controls Review Specialist', 'email' => 'controls.review@example.com', 'role' => 'auditor', 'department' => 'AUD'],
            ['name' => 'Inactive Auditor', 'email' => 'inactive.auditor@example.com', 'role' => 'auditor', 'department' => 'AUD', 'active' => false],
            ['name' => 'Inactive Finance User', 'email' => 'inactive.auditee@example.com', 'role' => 'auditee', 'department' => 'FIN', 'active' => false],
        ];

        $auditeesByDepartment = [
            'IT' => [
                ['name' => 'Core Banking Platform Owner', 'email' => 'core.banking.owner@example.com'],
                ['name' => 'Infrastructure Operations Manager', 'email' => 'it.infrastructure@example.com'],
                ['name' => 'Technology Service Lead', 'email' => 'it.service.lead@example.com'],
            ],
            'FIN' => [
                ['name' => 'Finance Auditee', 'email' => 'finance.auditee@example.com'],
                ['name' => 'Financial Control Manager', 'email' => 'finance.controls@example.com'],
                ['name' => 'Financial Reporting Lead', 'email' => 'financial.reporting@example.com'],
            ],
            'OPS' => [
                ['name' => 'Operations Auditee', 'email' => 'ops.auditee@example.com'],
                ['name' => 'Branch Operations Manager', 'email' => 'branch.operations@example.com'],
                ['name' => 'Payments Operations Lead', 'email' => 'payments.operations@example.com'],
            ],
            'HR' => [
                ['name' => 'HR Business Partner', 'email' => 'hr.auditee@example.com'],
                ['name' => 'Talent Operations Manager', 'email' => 'hr.talent@example.com'],
                ['name' => 'People Services Lead', 'email' => 'hr.services@example.com'],
            ],
            'CBK' => [
                ['name' => 'Corporate Banking Lead', 'email' => 'corporate.auditee@example.com'],
                ['name' => 'Relationship Banking Manager', 'email' => 'corporate.relationships@example.com'],
                ['name' => 'Trade Finance Control Owner', 'email' => 'trade.finance.owner@example.com'],
            ],
            'TRS' => [
                ['name' => 'Treasury Control Owner', 'email' => 'treasury.auditee@example.com'],
                ['name' => 'Liquidity Risk Manager', 'email' => 'treasury.liquidity@example.com'],
                ['name' => 'Markets Operations Lead', 'email' => 'markets.operations@example.com'],
            ],
            'RCO' => [
                ['name' => 'Risk & Compliance Officer', 'email' => 'risk.auditee@example.com'],
                ['name' => 'Operational Risk Manager', 'email' => 'operational.risk@example.com'],
                ['name' => 'Financial Crime Control Lead', 'email' => 'financial.crime@example.com'],
            ],
            'RBT' => [
                ['name' => 'Retail Banking Manager', 'email' => 'retail.auditee@example.com'],
                ['name' => 'Branch Network Lead', 'email' => 'retail.branches@example.com'],
                ['name' => 'Customer Service Control Owner', 'email' => 'retail.customer.service@example.com'],
            ],
            'ENT' => [
                ['name' => 'Enterprise Services Manager', 'email' => 'enterprise.auditee@example.com'],
                ['name' => 'Facilities Control Owner', 'email' => 'enterprise.facilities@example.com'],
                ['name' => 'Business Continuity Lead', 'email' => 'enterprise.continuity@example.com'],
            ],
            'SEC' => [
                ['name' => 'Information Security Manager', 'email' => 'security.manager@example.com'],
                ['name' => 'Security Operations Lead', 'email' => 'security.operations@example.com'],
                ['name' => 'Identity Governance Owner', 'email' => 'identity.governance@example.com'],
            ],
            'DCH' => [
                ['name' => 'Digital Channels Manager', 'email' => 'digital.channels@example.com'],
                ['name' => 'Mobile Banking Product Owner', 'email' => 'mobile.banking@example.com'],
                ['name' => 'Online Services Control Owner', 'email' => 'online.services@example.com'],
            ],
            'LND' => [
                ['name' => 'Lending Operations Manager', 'email' => 'lending.operations@example.com'],
                ['name' => 'Credit Underwriting Lead', 'email' => 'credit.underwriting@example.com'],
                ['name' => 'Loan Servicing Control Owner', 'email' => 'loan.servicing@example.com'],
            ],
            'PAY' => [
                ['name' => 'Payments Product Manager', 'email' => 'payments.product@example.com'],
                ['name' => 'Card Operations Lead', 'email' => 'card.operations@example.com'],
                ['name' => 'Settlement Control Owner', 'email' => 'payments.settlement@example.com'],
            ],
            'PRC' => [
                ['name' => 'Vendor Risk Manager', 'email' => 'vendor.risk@example.com'],
                ['name' => 'Procurement Operations Lead', 'email' => 'procurement.operations@example.com'],
                ['name' => 'Third-Party Oversight Owner', 'email' => 'third.party.oversight@example.com'],
            ],
            'LEG' => [
                ['name' => 'Legal Operations Manager', 'email' => 'legal.operations@example.com'],
                ['name' => 'Corporate Secretariat Lead', 'email' => 'corporate.secretariat@example.com'],
                ['name' => 'Regulatory Affairs Counsel', 'email' => 'regulatory.affairs@example.com'],
            ],
        ];

        foreach ($auditeesByDepartment as $departmentCode => $auditees) {
            foreach ($auditees as $auditee) {
                $users[] = [
                    ...$auditee,
                    'role' => 'auditee',
                    'department' => $departmentCode,
                ];
            }
        }

        foreach ($users as $data) {
            $role = Role::where('slug', $data['role'])->firstOrFail();

            $user = User::firstOrCreate(
                ['email' => $data['email']],
                [
                    'uuid' => (string) Str::uuid(),
                    'name' => $data['name'],
                    'password' => Hash::make('password'),
                    'role_id' => $role->id,
                    'department_id' => $departments[$data['department']],
                    'active' => $data['active'] ?? true,
                ]
            );

            $user->forceFill([
                'name' => $data['name'],
                'role_id' => $role->id,
                'department_id' => $departments[$data['department']],
                'active' => $data['active'] ?? true,
            ])->save();
        }
    }
}
