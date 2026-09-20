<?php

namespace App\Actions;

use App\Models\SalesCase;
use App\Models\User;
use App\SalesCaseStatus;
use App\Services\UnitStatusResolver;
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

            app(UnitStatusResolver::class)->reconcile($case->unit_id);

            return $case->refresh();
        });
    }
}
