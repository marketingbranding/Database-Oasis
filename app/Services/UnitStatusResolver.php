<?php

namespace App\Services;

use App\Models\Unit;
use App\SalesCaseStatus;
use App\UnitStatus;

final class UnitStatusResolver
{
    public function resolve(Unit $unit): UnitStatus
    {
        if ($unit->salesCases()->whereHas('akad')->exists()) {
            return UnitStatus::Terjual;
        }

        return $unit->salesCases()->where('case_status', SalesCaseStatus::Active->value)->exists()
            ? UnitStatus::Booking
            : UnitStatus::Tersedia;
    }

    public function reconcile(Unit|string|null $unit): ?Unit
    {
        $unit = $unit instanceof Unit ? $unit : ($unit === null ? null : Unit::find($unit));

        if ($unit === null) {
            return null;
        }

        $unit->update(['status' => $this->resolve($unit)->value]);

        return $unit->refresh();
    }
}
