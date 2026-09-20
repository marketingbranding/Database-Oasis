<?php

namespace App\Actions;

use App\FinancingType;
use App\Models\Consumer;
use App\Models\Project;
use App\Models\SalesCase;
use App\Models\Unit;
use App\Models\User;
use App\SalesCaseStage;
use App\SalesCaseStatus;
use App\Services\UnitStatusResolver;
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
            $financingType = $data['financing_type'] instanceof FinancingType
                ? $data['financing_type']
                : FinancingType::from($data['financing_type']);

            /** @var Unit|null $unit */
            $unit = filled($data['unit_id'] ?? null)
                ? Unit::whereKey($data['unit_id'])->lockForUpdate()->firstOrFail()
                : null;

            if ($unit !== null && ! Unit::available()->whereKey($unit->id)->exists()) {
                throw ValidationException::withMessages(['unit_id' => 'Kavling tidak tersedia untuk transaksi baru.']);
            }
            /** @var Project $project */
            $project = Project::whereKey($unit?->project_id ?? ($data['project_id'] ?? null))->firstOrFail();

            $branchId = $project->branch_id;

            if ($user->isBranchScoped() && ! $user->belongsToBranch($branchId)) {
                throw ValidationException::withMessages(['project_id' => 'Project berada di luar cabang Anda.']);
            }

            // One consumer may hold several ACTIVE sales cases (one
            // id_transaksi_v2 = one independent case), so no consumer-level
            // ACTIVE guard exists here. Only the unit guard above applies.
            $consumer = $this->resolveConsumer($user, $data);

            try {
                /** @var SalesCase $case */
                $case = SalesCase::create([
                    'consumer_id' => $consumer->id,
                    'unit_id' => $unit?->id,
                    'project_id' => $project->id,
                    'branch_id' => $branchId,
                    'financing_type' => $financingType,
                    'booking_date' => $data['booking_date'] ?? null,
                    'source' => $data['source'] ?? null,
                    'sales_pic_id' => $data['sales_pic_id'] ?? null,
                    'coordinator_id' => $data['coordinator_id'] ?? null,
                    'current_stage' => $financingType === FinancingType::Cash ? SalesCaseStage::Psjb : SalesCaseStage::BiChecking,
                    'case_status' => SalesCaseStatus::Active,
                    'created_by' => $user->id,
                ]);
            } catch (UniqueConstraintViolationException) {
                throw ValidationException::withMessages(['unit_id' => 'Unit sudah memiliki sales case aktif.']);
            }

            app(UnitStatusResolver::class)->reconcile($unit);

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
