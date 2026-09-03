<?php

namespace Database\Factories;

use App\DpStatus;
use App\Models\AkadReadiness;
use App\Models\SalesCase;
use App\ReadinessIssueStatus;
use App\ReadinessUtilityStatus;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<AkadReadiness> */
class AkadReadinessFactory extends Factory
{
    public function definition(): array
    {
        return [
            'sales_case_id' => SalesCase::factory(),
            'building_progress' => null,
            'building_status' => ReadinessIssueStatus::Unknown,
            'dp_status' => DpStatus::Unknown,
            'electricity_status' => ReadinessUtilityStatus::Unknown,
            'water_status' => ReadinessUtilityStatus::Unknown,
            'consumer_status' => ReadinessIssueStatus::Unknown,
        ];
    }
}
