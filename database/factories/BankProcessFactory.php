<?php

namespace Database\Factories;

use App\BankResponseType;
use App\Models\BankProcess;
use App\Models\DocumentSubmission;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<BankProcess>
 */
class BankProcessFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $submission = DocumentSubmission::factory()->create();

        return [
            'sales_case_id' => $submission->sales_case_id,
            'document_submission_id' => $submission->id,
            'bank_id' => $submission->bank_id,
            'response_type' => BankResponseType::Process,
            'response_date' => now()->toDateString(),
            'is_authoritative' => false,
            'created_by' => User::factory(),
        ];
    }

    public function approved(): static
    {
        return $this->state(fn (): array => [
            'response_type' => BankResponseType::Approved,
            'sp3k_number' => 'SP3K-'.fake()->numerify('#####'),
            'sp3k_date' => now()->toDateString(),
            'is_authoritative' => true,
        ]);
    }
}
