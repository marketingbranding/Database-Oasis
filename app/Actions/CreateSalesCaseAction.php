<?php

namespace App\Actions;

use App\Models\Consumer;
use App\Models\SalesCase;
use App\Models\Unit;
use App\Models\User;
use App\SalesCaseStage;
use App\SalesCaseStatus;
use App\UnitStatus;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\ValidationException;

class CreateSalesCaseAction
{
    /**
     * @param  array<string, mixed>  $data  unit_id, financing_type, booking_date, source, sales_pic_id, coordinator_id, and either consumer_id or consumer_attributes{nik, name, phone}
     */
    public function handle(User $user, array $data): SalesCase
    {
        Gate::forUser($user)->authorize('create', SalesCase::class);

        return DB::transaction(function () use ($user, $data): SalesCase {
            /** @var Unit $unit */
            $unit = Unit::with('project')->whereKey($data['unit_id'] ?? null)->lockForUpdate()->firstOrFail();

            $branchId = $unit->project->branch_id;

            if ($user->isBranchScoped() && ! $user->belongsToBranch($branchId)) {
                throw ValidationException::withMessages(['unit_id' => 'Unit berada di luar cabang Anda.']);
            }

            if ($unit->activeSalesCase()->exists()) {
                throw ValidationException::withMessages(['unit_id' => 'Unit sudah memiliki sales case aktif.']);
            }

            $consumer = $this->resolveConsumer($user, $data);

            if (SalesCase::query()
                ->whereBelongsTo($consumer)
                ->where('case_status', SalesCaseStatus::Active->value)
                ->exists()) {
                throw ValidationException::withMessages(['consumer_id' => 'Konsumen sudah memiliki sales case aktif.']);
            }

            try {
                /** @var SalesCase $case */
                $case = SalesCase::create([
                    'consumer_id' => $consumer->id,
                    'unit_id' => $unit->id,
                    'project_id' => $unit->project_id,
                    'branch_id' => $branchId,
                    'financing_type' => $data['financing_type'] ?? null,
                    'booking_date' => $data['booking_date'] ?? null,
                    'source' => $data['source'] ?? null,
                    'sales_pic_id' => $data['sales_pic_id'] ?? null,
                    'coordinator_id' => $data['coordinator_id'] ?? null,
                    'current_stage' => SalesCaseStage::DataKonsumen,
                    'case_status' => SalesCaseStatus::Active,
                    'created_by' => $user->id,
                ]);
            } catch (UniqueConstraintViolationException) {
                // A concurrent transaction created an ACTIVE case for this unit or consumer first.
                throw ValidationException::withMessages(['unit_id' => 'Unit atau konsumen sudah memiliki sales case aktif.']);
            }

            $unit->update(['status' => UnitStatus::Booking]);

            return $case;
        });
    }

    /**
     * @param  array<string, mixed>  $data
     */
    private function resolveConsumer(User $user, array $data): Consumer
    {
        if (filled($data['consumer_id'] ?? null)) {
            /** @var Consumer $consumer */
            $consumer = Consumer::whereKey($data['consumer_id'])->firstOrFail();

            return $consumer;
        }

        $attributes = (array) ($data['consumer_attributes'] ?? []);

        if (blank($attributes['nik'] ?? null) || blank($attributes['name'] ?? null)) {
            throw ValidationException::withMessages(['new_consumer_nik' => 'NIK dan nama konsumen wajib diisi.']);
        }

        try {
            /** @var Consumer $consumer */
            $consumer = Consumer::create([
                'nik' => $attributes['nik'],
                'name' => $attributes['name'],
                'phone' => $attributes['phone'] ?? null,
            ]);

            return $consumer;
        } catch (UniqueConstraintViolationException) {
            throw ValidationException::withMessages(['new_consumer_nik' => 'NIK sudah terdaftar. Gunakan konsumen yang sudah ada.']);
        }
    }
}
