<?php

namespace App\Actions;

use App\DocumentSubmissionStatus;
use App\DocumentSubmissionType;
use App\FinancingType;
use App\Models\DocumentSubmission;
use App\Models\SalesCase;
use App\Models\User;
use App\SalesCaseStage;
use App\SalesCaseStatus;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\ValidationException;

class CompleteCashPemberkasanAction
{
    public function handle(User $user, SalesCase $case): DocumentSubmission
    {
        Gate::forUser($user)->authorize('update', $case);

        return DB::transaction(function () use ($user, $case): DocumentSubmission {
            /** @var SalesCase $case */
            $case = SalesCase::whereKey($case->id)->lockForUpdate()->firstOrFail();

            if ($user->isBranchScoped() && ! $user->belongsToBranch($case->branch_id)) {
                throw ValidationException::withMessages(['sales_case_id' => 'Sales case berada di luar cabang Anda.']);
            }

            if ($case->case_status !== SalesCaseStatus::Active) {
                throw ValidationException::withMessages(['sales_case_id' => 'Sales case tidak aktif.']);
            }

            if ($case->financing_type !== FinancingType::Cash) {
                throw ValidationException::withMessages(['sales_case_id' => 'Pemberkasan CASH hanya untuk sales case CASH.']);
            }

            if (! $case->activePsjb()->exists()) {
                throw ValidationException::withMessages(['sales_case_id' => 'Sales case tidak memiliki PSJB aktif.']);
            }

            if ($case->documentSubmissions()
                ->where('type', DocumentSubmissionType::CashInternal->value)
                ->whereNotIn('status', [DocumentSubmissionStatus::Cancelled->value])
                ->exists()) {
                throw ValidationException::withMessages(['sales_case_id' => 'Pemberkasan CASH sudah selesai.']);
            }

            $sequence = ((int) DocumentSubmission::query()
                ->withTrashed()
                ->where('sales_case_id', $case->id)
                ->max('sequence')) + 1;

            try {
                /** @var DocumentSubmission $submission */
                $submission = DocumentSubmission::create([
                    'sales_case_id' => $case->id,
                    'psjb_id' => $case->activePsjb()->firstOrFail()->id,
                    'bank_id' => null,
                    'submission_date' => now()->toDateString(),
                    'sequence' => $sequence,
                    'status' => DocumentSubmissionStatus::Submitted,
                    'type' => DocumentSubmissionType::CashInternal,
                    'created_by' => $user->id,
                ]);
            } catch (UniqueConstraintViolationException) {
                throw ValidationException::withMessages(['sales_case_id' => 'Nomor urut pemberkasan bentrok. Silakan ulangi.']);
            }

            $case->advanceStageTo(SalesCaseStage::PpjbDev);

            return $submission;
        });
    }
}
