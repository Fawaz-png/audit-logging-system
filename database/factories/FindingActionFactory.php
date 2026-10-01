<?php

namespace Database\Factories;

use App\Models\Department;
use App\Models\Finding;
use App\Models\FindingAction;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<FindingAction>
 */
class FindingActionFactory extends Factory
{
    protected $model = FindingAction::class;

    public function definition(): array
    {
        $finding = Finding::query()->inRandomOrder()->first() ?? Finding::factory()->create();
        $department = Department::query()->inRandomOrder()->first();
        $user = User::query()->inRandomOrder()->first() ?? User::factory()->create();

        return [
            'finding_id' => $finding->id,
            'responsible_department_id' => $department?->id ?? $finding->audit->department_id,
            'responsible_user_id' => $user->id,
            'action' => $this->faker->sentence(8),
            'proposed_due_date' => now()->addDays(14)->toDateString(),
            'agreed_due_date' => now()->addDays(10)->toDateString(),
            'status' => FindingAction::STATUS_PENDING_APPROVAL,
            'approved_by' => null,
            'approved_at' => null,
            'approval_comment' => null,
            'completed_at' => null,
            'rejection_reason' => null,
            'created_by' => $user->id,
            'updated_by' => $user->id,
        ];
    }

    public function approved(): static
    {
        return $this->state([
            'status' => FindingAction::STATUS_APPROVED,
            'approved_by' => User::query()->inRandomOrder()->first()?->id,
            'approved_at' => now(),
            'approval_comment' => 'Approved by lead auditor.',
        ]);
    }

    public function inProgress(): static
    {
        return $this->state([
            'status' => FindingAction::STATUS_IN_PROGRESS,
            'agreed_due_date' => now()->addDays(10)->toDateString(),
            'approved_by' => User::query()->inRandomOrder()->first()?->id,
            'approved_at' => now(),
        ]);
    }

    public function completed(): static
    {
        return $this->state([
            'status' => FindingAction::STATUS_COMPLETED,
            'approved_by' => User::query()->inRandomOrder()->first()?->id,
            'approved_at' => now()->subDays(3),
            'completed_at' => now()->subDay(),
        ]);
    }

    public function overdue(): static
    {
        return $this->state([
            'status' => FindingAction::STATUS_IN_PROGRESS,
            'agreed_due_date' => now()->subDays(5)->toDateString(),
            'approved_by' => User::query()->inRandomOrder()->first()?->id,
            'approved_at' => now()->subDays(10),
        ]);
    }
}
