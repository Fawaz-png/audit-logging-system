<?php

namespace Database\Factories;

use App\Models\Audit;
use App\Models\Department;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Audit>
 */
class AuditFactory extends Factory
{
    protected $model = Audit::class;

    public function definition(): array
    {
        $department = Department::query()->inRandomOrder()->first()
            ?? Department::query()->first();

        $createdBy = User::query()->inRandomOrder()->first()
            ?? User::factory()->create();

        return [
            'reference' => $this->faker->unique()->bothify('AUD-????-###'),
            'title' => $this->faker->sentence(4),
            'objective' => $this->faker->paragraph(),
            'scope' => $this->faker->paragraph(),
            'department_id' => $department?->id ?? Department::query()->first()?->id,
            'audit_mode' => 'ONSITE',
            'location' => $this->faker->city(),
            'methodology' => 'RISK_BASED',
            'previous_audit_reference' => null,
            'start_date' => now()->subMonths(2)->toDateString(),
            'end_date' => now()->subDays(3)->toDateString(),
            'status' => 'COMPLETED',
            'created_by' => $createdBy->id,
        ];
    }

    public function active(): static
    {
        return $this->state([
            'status' => 'IN_PROGRESS',
            'start_date' => now()->subWeeks(2)->toDateString(),
            'end_date' => null,
        ]);
    }

    public function planned(): static
    {
        return $this->state([
            'status' => 'PLANNED',
            'start_date' => now()->addWeek()->toDateString(),
            'end_date' => null,
        ]);
    }

    public function remote(): static
    {
        return $this->state([
            'audit_mode' => 'REMOTE',
            'location' => null,
        ]);
    }

    public function hybrid(): static
    {
        return $this->state([
            'audit_mode' => 'HYBRID',
        ]);
    }

    public function concluded(): static
    {
        return $this->state([
            'status' => 'COMPLETED',
            'end_date' => now()->subDays(3)->toDateString(),
        ]);
    }
}
