<?php

namespace App\Actions;

use App\FinancingType;
use App\Models\SalesCase;
use App\Models\User;
use App\SalesCaseStage;
use App\SalesCaseStatus;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\ValidationException;

class AdvanceCashCaseToPpjbAction
{
    public function handle(User $user, SalesCase $case): SalesCase
    {
        Gate::forUser($user)->authorize('update', $case);

        return DB::transaction(function () use ($user, $case): SalesCase {
            /** @var SalesCase $case */
            $case = SalesCase::whereKey($case->id)->lockForUpdate()->firstOrFail();

            if ($user->isBranchScoped() && ! $user->belongsToBranch($case->branch_id)) {
                throw ValidationException::withMessages(['sales_case_id' => 'Sales case berada di luar cabang Anda.']);
            }

            if ($case->case_status !== SalesCaseStatus::Active) {
                throw ValidationException::withMessages(['sales_case_id' => 'Sales case tidak aktif.']);
            }

            if ($case->financing_type !== FinancingType::Cash) {
                throw ValidationException::withMessages(['sales_case_id' => 'Action ini hanya untuk sales case CASH.']);
            }

            if (! $case->activePsjb()->exists()) {
                throw ValidationException::withMessages(['sales_case_id' => 'Sales case tidak memiliki PSJB aktif.']);
            }

            $case->advanceStageTo(SalesCaseStage::PpjbDev);

            return $case->refresh();
        });
    }
}
