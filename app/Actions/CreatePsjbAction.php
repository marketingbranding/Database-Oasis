<?php

namespace App\Actions;

use App\BiCheckResult;
use App\FinancingType;
use App\Models\BiCheck;
use App\Models\Psjb;
use App\Models\SalesCase;
use App\Models\User;
use App\PsjbStatus;
use App\SalesCaseStage;
use App\SalesCaseStatus;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\ValidationException;

class CreatePsjbAction
{
    /**
     * @param  array<string, mixed>  $data  sales_case_id, psjb_date, document_number, coordinator_id, notes
     */
    public function handle(User $user, array $data): Psjb
    {
        Gate::forUser($user)->authorize('create', Psjb::class);

        return DB::transaction(function () use ($user, $data): Psjb {
            /** @var SalesCase $case */
            $case = SalesCase::whereKey($data['sales_case_id'] ?? null)->lockForUpdate()->firstOrFail();

            if ($user->isBranchScoped() && ! $user->belongsToBranch($case->branch_id)) {
                throw ValidationException::withMessages(['sales_case_id' => 'Sales case berada di luar cabang Anda.']);
            }

            if ($case->case_status !== SalesCaseStatus::Active) {
                throw ValidationException::withMessages(['sales_case_id' => 'Sales case tidak aktif.']);
            }

            if ($case->financing_type === FinancingType::KprSubsidi) {
                $latestBi = BiCheck::latestForCase($case->id);

                if ($latestBi === null || $latestBi->result !== BiCheckResult::Clear) {
                    throw ValidationException::withMessages(['sales_case_id' => 'BI checking terakhir belum CLEAR. PSJB tidak dapat dibuat.']);
                }
            }

            if ($case->activePsjb()->exists()) {
                throw ValidationException::withMessages(['sales_case_id' => 'Sales case sudah memiliki PSJB aktif. Gunakan reissue.']);
            }

            try {
                /** @var Psjb $psjb */
                $psjb = Psjb::create([
                    'sales_case_id' => $case->id,
                    'psjb_date' => $data['psjb_date'],
                    'document_number' => $data['document_number'] ?? null,
                    // Historical snapshot: defaults from the case at creation time only.
                    'coordinator_id' => $data['coordinator_id'] ?? $case->coordinator_id,
                    'status' => PsjbStatus::Active,
                    'notes' => $data['notes'] ?? null,
                    'created_by' => $user->id,
                ]);
            } catch (UniqueConstraintViolationException) {
                // A concurrent transaction created the ACTIVE PSJB first.
                throw ValidationException::withMessages(['sales_case_id' => 'Sales case sudah memiliki PSJB aktif.']);
            }

            $case->advanceStageTo(SalesCaseStage::Pemberkasan);

            return $psjb;
        });
    }
}
