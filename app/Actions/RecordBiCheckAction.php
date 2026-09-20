<?php

namespace App\Actions;

use App\Models\BiCheck;
use App\Models\SalesCase;
use App\Models\User;
use App\SalesCaseStatus;
use App\Services\SalesCaseStageResolver;
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

            app(SalesCaseStageResolver::class)->reconcile($case);

            return $biCheck;
        });
    }
}
