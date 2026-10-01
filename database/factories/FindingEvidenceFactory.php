<?php

namespace Database\Factories;

use App\Models\Finding;
use App\Models\FindingEvidence;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<FindingEvidence>
 */
class FindingEvidenceFactory extends Factory
{
    protected $model = FindingEvidence::class;

    public function definition(): array
    {
        $finding = Finding::query()->inRandomOrder()->first() ?? Finding::factory()->create();
        $user = User::query()->inRandomOrder()->first() ?? User::factory()->create();

        return [
            'finding_id' => $finding->id,
            'type' => FindingEvidence::TYPE_FINDING,
            'uploaded_by' => $user->id,
            'original_name' => 'supporting-evidence.txt',
            'path' => 'finding-evidence/'.$finding->uuid.'/supporting-evidence.txt',
            'disk' => 'local',
            'mime_type' => 'text/plain',
            'size' => 256,
            'description' => 'Factory-generated evidence supporting the finding.',
        ];
    }

    public function findingEvidence(): static
    {
        return $this->state([
            'type' => FindingEvidence::TYPE_FINDING,
        ]);
    }

    public function resolutionEvidence(): static
    {
        return $this->state([
            'type' => FindingEvidence::TYPE_RESOLUTION,
            'description' => 'Closing evidence submitted by the auditee.',
        ]);
    }
}
