<?php

namespace Database\Factories;

use App\FinancingType;
use App\Models\Consumer;
use App\Models\SalesCase;
use App\Models\Unit;
use App\Models\User;
use App\SalesCaseStage;
use App\SalesCaseStatus;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<SalesCase>
 */
class SalesCaseFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $unit = Unit::factory()->create();

        return [
            'consumer_id' => Consumer::factory(),
            'unit_id' => $unit->id,
            'project_id' => $unit->project_id,
            'branch_id' => $unit->project->branch_id,
            'financing_type' => FinancingType::KprSubsidi,
            'current_stage' => SalesCaseStage::DataKonsumen,
            'case_status' => SalesCaseStatus::Active,
            'created_by' => User::factory(),
        ];
    }

    public function forUnit(Unit $unit): static
    {
        return $this->state(fn () => [
            'unit_id' => $unit->id,
            'project_id' => $unit->project_id,
            'branch_id' => $unit->project->branch_id,
        ]);
    }

    public function closed(SalesCaseStatus $status, ?string $reason = null): static
    {
        return $this->state(fn () => [
            'case_status' => $status,
            'closed_at' => now(),
            'closed_reason' => $reason,
        ]);
    }
}
