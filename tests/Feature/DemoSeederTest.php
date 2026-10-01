<?php

namespace Tests\Feature;

use App\Models\Department;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class DemoSeederTest extends TestCase
{
    use RefreshDatabase;

    public function test_demo_data_seeder_is_repeatable(): void
    {
        Storage::fake('local');

        $this->seed();

        $this->assertDatabaseCount('departments', 16);
        $this->assertDatabaseCount('users', 54);
        $this->assertDatabaseCount('audits', 15);
        $this->assertDatabaseCount('findings', 45);
        $this->assertDatabaseCount('finding_evidences', 65);

        $auditDepartment = Department::query()->where('code', 'AUD')->firstOrFail();
        $this->assertSame(5, User::query()
            ->where('department_id', $auditDepartment->id)
            ->where('active', true)
            ->whereHas('role', fn ($query) => $query->where('slug', 'auditor'))
            ->count());

        foreach (Department::query()->where('code', '!=', 'AUD')->get() as $department) {
            $this->assertSame(3, User::query()
                ->where('department_id', $department->id)
                ->where('active', true)
                ->whereHas('role', fn ($query) => $query->where('slug', 'auditee'))
                ->count());
        }

        $this->seed();

        $this->assertDatabaseCount('departments', 16);
        $this->assertDatabaseCount('users', 54);
        $this->assertDatabaseCount('audits', 15);
        $this->assertDatabaseCount('findings', 45);
        $this->assertDatabaseCount('finding_evidences', 65);
    }
}
