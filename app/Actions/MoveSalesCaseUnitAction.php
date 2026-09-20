<?php

namespace App\Actions;

use App\Models\CaseNote;
use App\Models\SalesCase;
use App\Models\Unit;
use App\Models\User;
use App\SalesCaseStatus;
use App\Services\UnitStatusResolver;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\ValidationException;

class MoveSalesCaseUnitAction
{
    /**
     * Move a sales case to another unit in place.
     *
     * A unit transfer is an event on the same sales case, not a terminal
     * status: the old unit is released, the new unit becomes current, and the
     * case remains ACTIVE. The transfer stays traceable through
     * transfer_reason plus a case note recording the previous unit.
     */
    public function handle(User $user, SalesCase $case, string $newUnitId, string $transferReason): SalesCase
    {
        Gate::forUser($user)->authorize('update', $case);

        return DB::transaction(function () use ($user, $case, $newUnitId, $transferReason): SalesCase {
            /** @var SalesCase $case */
            $case = SalesCase::whereKey($case->id)->lockForUpdate()->firstOrFail();

            if ($case->case_status !== SalesCaseStatus::Active) {
                throw ValidationException::withMessages(['case_status' => 'Sales case sudah tidak aktif.']);
            }

            if ($case->akad()->exists()) {
                throw ValidationException::withMessages(['case_status' => 'Sales case tidak dapat pindah kavling setelah Akad.']);
            }

            // Lock both units in a deterministic order to avoid deadlocks between opposing moves.
            $units = Unit::query()
                ->with('project')
                ->whereIn('id', [$case->unit_id, $newUnitId])
                ->orderBy('id')
                ->lockForUpdate()
                ->get();

            /** @var Unit|null $oldUnit */
            $oldUnit = $units->firstWhere('id', $case->unit_id);
            /** @var Unit|null $newUnit */
            $newUnit = $units->firstWhere('id', $newUnitId);

            if ($newUnit === null) {
                throw ValidationException::withMessages(['new_unit_id' => 'Unit baru tidak ditemukan.']);
            }

            $newUnitBranchId = $newUnit->project->branch_id;

            if ($user->isBranchScoped() && ! $user->belongsToBranch($newUnitBranchId)) {
                throw ValidationException::withMessages(['new_unit_id' => 'Unit baru berada di luar cabang Anda.']);
            }

            if ($newUnitBranchId !== $case->branch_id) {
                throw ValidationException::withMessages(['new_unit_id' => 'Pindah kavling hanya boleh dalam satu cabang.']);
            }

            if ($newUnit->id === $case->unit_id) {
                throw ValidationException::withMessages(['new_unit_id' => 'Unit baru sama dengan unit saat ini.']);
            }

            if (! Unit::available()->whereKey($newUnit->id)->exists()) {
                throw ValidationException::withMessages(['new_unit_id' => 'Kavling tujuan tidak tersedia.']);
            }

            $oldUnitCode = $oldUnit?->unit_code;

            $case->update([
                'unit_id' => $newUnit->id,
                'project_id' => $newUnit->project_id,
                'transfer_reason' => $transferReason,
            ]);

            $resolver = app(UnitStatusResolver::class);
            $resolver->reconcile($oldUnit);
            $resolver->reconcile($newUnit);

            CaseNote::create([
                'sales_case_id' => $case->id,
                'note' => $oldUnitCode === null
                    ? "Penempatan kavling ke {$newUnit->unit_code}: {$transferReason}"
                    : "Pindah kavling dari {$oldUnitCode} ke {$newUnit->unit_code}: {$transferReason}",
                'created_by' => $user->id,
            ]);

            return $case->refresh();
        });
    }
}
