<?php

namespace App\Actions;

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

class ReissuePsjbAction
{
    /**
     * Supersedes the current ACTIVE PSJB with a new one. The old record is
     * never mutated into the new document; history is preserved.
     *
     * @param  array<string, mixed>  $data  psjb_date, document_number, coordinator_id, notes
     */
    public function handle(User $user, SalesCase $case, array $data): Psjb
    {
        Gate::forUser($user)->authorize('create', Psjb::class);

        return DB::transaction(function () use ($user, $case, $data): Psjb {
            /** @var SalesCase $case */
            $case = SalesCase::whereKey($case->id)->lockForUpdate()->firstOrFail();

            if ($user->isBranchScoped() && ! $user->belongsToBranch($case->branch_id)) {
                throw ValidationException::withMessages(['sales_case_id' => 'Sales case berada di luar cabang Anda.']);
            }

            if ($case->case_status !== SalesCaseStatus::Active) {
                throw ValidationException::withMessages(['sales_case_id' => 'Sales case tidak aktif.']);
            }

            /** @var Psjb|null $currentPsjb */
            $currentPsjb = Psjb::query()
                ->where('sales_case_id', $case->id)
                ->where('status', PsjbStatus::Active->value)
                ->lockForUpdate()
                ->first();

            if ($currentPsjb === null) {
                throw ValidationException::withMessages(['sales_case_id' => 'Tidak ada PSJB aktif untuk di-reissue.']);
            }

            $currentPsjb->update(['status' => PsjbStatus::Superseded]);

            try {
                /** @var Psjb $newPsjb */
                $newPsjb = Psjb::create([
                    'sales_case_id' => $case->id,
                    'psjb_date' => $data['psjb_date'],
                    'document_number' => $data['document_number'] ?? null,
                    'coordinator_id' => $data['coordinator_id'] ?? $case->coordinator_id,
                    'status' => PsjbStatus::Active,
                    'notes' => $data['notes'] ?? null,
                    'created_by' => $user->id,
                ]);
            } catch (UniqueConstraintViolationException) {
                // A concurrent transaction created the ACTIVE PSJB first.
                throw ValidationException::withMessages(['sales_case_id' => 'Sales case sudah memiliki PSJB aktif.']);
            }

            // Forward only: a case already past PEMBERKASAN keeps its position.
            $case->advanceStageTo(SalesCaseStage::Pemberkasan);

            return $newPsjb;
        });
    }
}
