<?php

namespace App\Actions;

use App\Models\Psjb;
use App\Models\SalesCase;
use App\Models\User;
use App\PsjbStatus;
use App\SalesCaseStage;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\ValidationException;

class CancelPsjbAction
{
    public function handle(User $user, Psjb $psjb): Psjb
    {
        Gate::forUser($user)->authorize('create', Psjb::class);

        return DB::transaction(function () use ($psjb): Psjb {
            /** @var Psjb $psjb */
            $psjb = Psjb::whereKey($psjb->id)->lockForUpdate()->firstOrFail();

            /** @var SalesCase $case */
            $case = SalesCase::whereKey($psjb->sales_case_id)->lockForUpdate()->firstOrFail();

            if ($psjb->status !== PsjbStatus::Active) {
                throw ValidationException::withMessages(['status' => 'PSJB tidak aktif.']);
            }

            if ($case->current_stage->isBeyond(SalesCaseStage::Pemberkasan)) {
                throw ValidationException::withMessages(['status' => 'Tidak dapat membatalkan PSJB setelah tahap lanjutan tercatat.']);
            }

            $psjb->update(['status' => PsjbStatus::Cancelled]);

            // No downstream progression exists yet, so the case moves back to
            // waiting for a PSJB. The Sales Case itself stays ACTIVE.
            $case->update(['current_stage' => SalesCaseStage::Psjb]);

            return $psjb->refresh();
        });
    }
}
