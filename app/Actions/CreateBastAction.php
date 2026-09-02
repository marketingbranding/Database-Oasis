<?php

namespace App\Actions;

use App\BastStatus;
use App\Models\AkadRecord;
use App\Models\BastRecord;
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

class CreateBastAction
{
    /** @param array<string, mixed> $data */
    public function handle(User $user, array $data): BastRecord
    {
        Gate::forUser($user)->authorize('create', BastRecord::class);

        return DB::transaction(function () use ($user, $data): BastRecord {
            /** @var SalesCase $case */
            $case = SalesCase::whereKey($data['sales_case_id'] ?? null)->lockForUpdate()->firstOrFail();
            if ($user->isBranchScoped() && ! $user->belongsToBranch($case->branch_id)) {
                throw ValidationException::withMessages(['sales_case_id' => 'Sales case berada di luar cabang Anda.']);
            }
            if ($case->case_status !== SalesCaseStatus::Active || $case->bast()->exists()) {
                throw ValidationException::withMessages(['sales_case_id' => 'Sales case tidak aktif atau sudah memiliki BAST.']);
            }
            /** @var AkadRecord|null $akad */
            $akad = AkadRecord::query()->whereKey($data['akad_id'] ?? null)->lockForUpdate()->first();
            if ($akad === null || $akad->sales_case_id !== $case->id) {
                throw ValidationException::withMessages(['akad_id' => 'Akad tidak terkait dengan sales case ini.']);
            }
            try {
                /** @var BastRecord $bast */
                $bast = BastRecord::create([
                    'sales_case_id' => $case->id, 'akad_id' => $akad->id,
                    'bast_number' => $data['bast_number'] ?? null, 'bast_date' => $data['bast_date'],
                    'status' => BastStatus::Completed, 'notes' => $data['notes'] ?? null, 'created_by' => $user->id,
                ]);
            } catch (UniqueConstraintViolationException) {
                throw ValidationException::withMessages(['sales_case_id' => 'Sales case atau Akad sudah memiliki BAST.']);
            }
            $case->update([
                'current_stage' => SalesCaseStage::Completed,
                'case_status' => SalesCaseStatus::Completed,
                'closed_at' => now(),
                'closed_reason' => 'BAST completed',
            ]);
            Unit::whereKey($case->unit_id)->update(['status' => UnitStatus::Terjual->value]);

            return $bast;
        });
    }
}
