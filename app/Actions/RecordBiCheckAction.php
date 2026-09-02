<?php

namespace App\Actions;

use App\BiCheckResult;
use App\Models\BiCheck;
use App\Models\SalesCase;
use App\Models\User;
use App\SalesCaseStage;
use App\SalesCaseStatus;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\ValidationException;

class RecordBiCheckAction
{
    /**
     * @param  array<string, mixed>  $data  sales_case_id, check_date, result, description
     */
    public function handle(User $user, array $data): BiCheck
    {
        Gate::forUser($user)->authorize('create', BiCheck::class);

        return DB::transaction(function () use ($user, $data): BiCheck {
            /** @var SalesCase $case */
            $case = SalesCase::whereKey($data['sales_case_id'] ?? null)->lockForUpdate()->firstOrFail();

            if ($user->isBranchScoped() && ! $user->belongsToBranch($case->branch_id)) {
                throw ValidationException::withMessages(['sales_case_id' => 'Sales case berada di luar cabang Anda.']);
            }

            if ($case->case_status !== SalesCaseStatus::Active) {
                throw ValidationException::withMessages(['sales_case_id' => 'Sales case tidak aktif.']);
            }

            /** @var BiCheck $biCheck */
            $biCheck = BiCheck::create([
                'sales_case_id' => $case->id,
                'check_date' => $data['check_date'],
                'result' => $data['result'],
                'description' => $data['description'] ?? null,
                'created_by' => $user->id,
            ]);

            $this->transitionStage($case, $data['result']);

            return $biCheck;
        });
    }

    private function transitionStage(SalesCase $case, mixed $result): void
    {
        if ($result === BiCheckResult::Clear) {
            // Forward only: a case already past PSJB keeps its position.
            $case->advanceStageTo(SalesCaseStage::Psjb);

            return;
        }

        // REVIEW/REJECTED keep the case in BI_CHECKING, but never regress a
        // case that has legitimately progressed beyond the PSJB stage.
        if (! $case->current_stage->isBeyond(SalesCaseStage::Psjb)) {
            $case->update(['current_stage' => SalesCaseStage::BiChecking]);
        }
    }
}
