<?php

namespace App\Actions;

use App\Models\SalesCase;
use App\Models\Unit;
use App\Models\User;
use App\SalesCaseStatus;
use App\UnitStatus;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\ValidationException;

abstract class CloseSalesCaseAction
{
    abstract protected function status(): SalesCaseStatus;

    public function handle(User $user, SalesCase $case, ?string $reason = null): SalesCase
    {
        Gate::forUser($user)->authorize('update', $case);

        return DB::transaction(function () use ($case, $reason): SalesCase {
            /** @var SalesCase $case */
            $case = SalesCase::whereKey($case->id)->lockForUpdate()->firstOrFail();

            if ($case->case_status !== SalesCaseStatus::Active) {
                throw ValidationException::withMessages(['case_status' => 'Sales case sudah tidak aktif.']);
            }

            if ($case->akad()->exists()) {
                throw ValidationException::withMessages(['case_status' => 'Sales case tidak dapat ditutup setelah Akad.']);
            }

            $case->update([
                'case_status' => $this->status(),
                'closed_at' => now(),
                'closed_reason' => $reason,
            ]);

            $this->releaseUnitIfNoActiveCase($case);

            return $case->refresh();
        });
    }

    protected function releaseUnitIfNoActiveCase(SalesCase $case): void
    {
        $stillActive = SalesCase::query()
            ->where('unit_id', $case->unit_id)
            ->where('case_status', SalesCaseStatus::Active->value)
            ->whereKeyNot($case->id)
            ->exists();

        if (! $stillActive) {
            Unit::whereKey($case->unit_id)->update(['status' => UnitStatus::Tersedia->value]);
        }
    }
}
