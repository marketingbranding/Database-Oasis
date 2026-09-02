<?php

namespace App\Actions;

use App\Models\SalesCase;
use App\Models\Unit;
use App\Models\User;
use App\SalesCaseStage;
use App\SalesCaseStatus;
use App\UnitStatus;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\ValidationException;

class MoveSalesCaseUnitAction
{
    public function handle(User $user, SalesCase $oldCase, string $newUnitId, string $transferReason): SalesCase
    {
        Gate::forUser($user)->authorize('update', $oldCase);

        return DB::transaction(function () use ($user, $oldCase, $newUnitId, $transferReason): SalesCase {
            /** @var SalesCase $oldCase */
            $oldCase = SalesCase::whereKey($oldCase->id)->lockForUpdate()->firstOrFail();

            if ($oldCase->case_status !== SalesCaseStatus::Active) {
                throw ValidationException::withMessages(['case_status' => 'Sales case sudah tidak aktif.']);
            }

            if ($oldCase->akad()->exists()) {
                throw ValidationException::withMessages(['case_status' => 'Sales case tidak dapat pindah kavling setelah Akad.']);
            }

            // Lock both units in a deterministic order to avoid deadlocks between opposing moves.
            $units = Unit::query()
                ->with('project')
                ->whereIn('id', [$oldCase->unit_id, $newUnitId])
                ->orderBy('id')
                ->lockForUpdate()
                ->get();

            /** @var Unit $oldUnit */
            $oldUnit = $units->firstWhere('id', $oldCase->unit_id);
            /** @var Unit|null $newUnit */
            $newUnit = $units->firstWhere('id', $newUnitId);

            if ($newUnit === null) {
                throw ValidationException::withMessages(['new_unit_id' => 'Unit baru tidak ditemukan.']);
            }

            $newUnitBranchId = $newUnit->project->branch_id;

            if ($user->isBranchScoped() && ! $user->belongsToBranch($newUnitBranchId)) {
                throw ValidationException::withMessages(['new_unit_id' => 'Unit baru berada di luar cabang Anda.']);
            }

            if ($newUnitBranchId !== $oldCase->branch_id) {
                throw ValidationException::withMessages(['new_unit_id' => 'Pindah kavling hanya boleh dalam satu cabang.']);
            }

            if ($newUnit->id === $oldCase->unit_id) {
                throw ValidationException::withMessages(['new_unit_id' => 'Unit baru sama dengan unit saat ini.']);
            }

            if ($newUnit->activeSalesCase()->exists()) {
                throw ValidationException::withMessages(['new_unit_id' => 'Unit baru sudah memiliki sales case aktif.']);
            }

            $consumerHasOtherActiveCase = SalesCase::query()
                ->whereBelongsTo($oldCase->consumer)
                ->where('case_status', SalesCaseStatus::Active->value)
                ->whereKeyNot($oldCase->id)
                ->exists();

            if ($consumerHasOtherActiveCase) {
                throw ValidationException::withMessages(['case_status' => 'Konsumen sudah memiliki sales case aktif lain.']);
            }

            $oldCase->update([
                'case_status' => SalesCaseStatus::PindahKavling,
                'closed_at' => now(),
            ]);

            Unit::whereKey($oldUnit->id)->update(['status' => UnitStatus::Tersedia->value]);

            /** @var SalesCase $newCase */
            $newCase = SalesCase::create([
                'consumer_id' => $oldCase->consumer_id,
                'unit_id' => $newUnit->id,
                'project_id' => $newUnit->project_id,
                'branch_id' => $newUnitBranchId,
                'financing_type' => $oldCase->financing_type,
                'source' => $oldCase->source,
                'sales_pic_id' => $oldCase->sales_pic_id,
                'coordinator_id' => $oldCase->coordinator_id,
                'current_stage' => SalesCaseStage::DataKonsumen,
                'case_status' => SalesCaseStatus::Active,
                'previous_case_id' => $oldCase->id,
                'transfer_reason' => $transferReason,
                'created_by' => $user->id,
            ]);

            $newUnit->update(['status' => UnitStatus::Booking]);

            return $newCase;
        });
    }
}
