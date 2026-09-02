<?php

namespace Database\Factories;

use App\DocumentSubmissionStatus;
use App\Models\Bank;
use App\Models\DocumentSubmission;
use App\Models\Psjb;
use App\Models\SalesCase;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<DocumentSubmission>
 */
class DocumentSubmissionFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $psjb = Psjb::factory()->create();

        return [
            'sales_case_id' => $psjb->sales_case_id,
            'psjb_id' => $psjb->id,
            'bank_id' => Bank::factory(),
            'submission_date' => now()->toDateString(),
            'sequence' => 1,
            'status' => DocumentSubmissionStatus::Submitted,
            'created_by' => User::factory(),
        ];
    }

    public function forCaseAndPsjb(SalesCase $case, Psjb $psjb): static
    {
        return $this->state(fn (): array => [
            'sales_case_id' => $case->id,
            'psjb_id' => $psjb->id,
        ]);
    }
}
