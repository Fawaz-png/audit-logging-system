<?php

namespace Database\Factories;

use App\Models\Audit;
use App\Models\Finding;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Finding>
 */
class FindingFactory extends Factory
{
    protected $model = Finding::class;

    public function definition(): array
    {
        $audit = Audit::query()->inRandomOrder()->first() ?? Audit::factory()->create();
        $creator = User::query()->inRandomOrder()->first() ?? User::factory()->create();

        return [
            'audit_id' => $audit->id,
            'reference' => $this->faker->unique()->bothify('FND-????-###'),
            'title' => $this->faker->sentence(5),
            'description' => $this->faker->paragraph(),
            'criteria' => $this->faker->sentence(),
            'effect' => $this->faker->sentence(),
            'recommendation' => $this->faker->sentence(),
            'process' => 'Access management',
            'risk_rating' => $this->faker->randomElement(['LOW', 'MEDIUM', 'HIGH', 'CRITICAL']),
            'status' => Finding::STATUS_OPEN,
            'created_by' => $creator->id,
            'updated_by' => $creator->id,
        ];
    }

    public function open(): static
    {
        return $this->state([
            'status' => Finding::STATUS_OPEN,
        ]);
    }

    public function responseReceived(): static
    {
        return $this->state([
            'status' => Finding::STATUS_RESPONSE_RECEIVED,
        ]);
    }

    public function actionPendingApproval(): static
    {
        return $this->state([
            'status' => Finding::STATUS_ACTION_PENDING_APPROVAL,
        ]);
    }

    public function remediationInProgress(): static
    {
        return $this->state([
            'status' => Finding::STATUS_REMEDIATION_IN_PROGRESS,
        ]);
    }

    public function readyForClosure(): static
    {
        return $this->state([
            'status' => Finding::STATUS_READY_FOR_VERIFICATION,
        ]);
    }

    public function closureRequested(): static
    {
        return $this->state([
            'status' => Finding::STATUS_CLOSURE_REQUESTED,
        ]);
    }

    public function closureRejected(): static
    {
        return $this->state([
            'status' => Finding::STATUS_CLOSURE_REJECTED,
        ]);
    }

    public function verified(): static
    {
        return $this->state([
            'status' => Finding::STATUS_VERIFIED,
        ]);
    }

    public function closed(): static
    {
        return $this->state([
            'status' => Finding::STATUS_CLOSED,
        ]);
    }
}
