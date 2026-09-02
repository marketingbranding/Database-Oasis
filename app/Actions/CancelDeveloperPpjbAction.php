<?php

namespace App\Actions;

use App\DeveloperPpjbStatus;
use App\Models\DeveloperPpjb;
use App\Models\SalesCase;
use App\Models\User;
use App\SalesCaseStage;
use App\SalesCaseStatus;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\ValidationException;

class CancelDeveloperPpjbAction
{
    public function handle(User $user, DeveloperPpjb $ppjb): DeveloperPpjb
    {
        Gate::forUser($user)->authorize('create', DeveloperPpjb::class);

        return DB::transaction(function () use ($ppjb): DeveloperPpjb {
            /** @var DeveloperPpjb $ppjb */
            $ppjb = DeveloperPpjb::whereKey($ppjb->id)->lockForUpdate()->firstOrFail();
            /** @var SalesCase $case */
            $case = SalesCase::whereKey($ppjb->sales_case_id)->lockForUpdate()->firstOrFail();
            if ($case->case_status !== SalesCaseStatus::Active || $ppjb->status !== DeveloperPpjbStatus::Active || $case->akad()->exists()) {
                throw ValidationException::withMessages(['status' => 'PPJB tidak dapat dibatalkan setelah Akad, saat tidak aktif, atau case ditutup.']);
            }
            $ppjb->update(['status' => DeveloperPpjbStatus::Cancelled]);
            if (! $case->current_stage->isBeyond(SalesCaseStage::Akad)) {
                $case->update(['current_stage' => SalesCaseStage::PpjbDev]);
            }

            return $ppjb->refresh();
        });
    }
}
