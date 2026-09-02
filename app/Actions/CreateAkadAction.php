<?php

namespace App\Actions;

use App\DeveloperPpjbStatus;
use App\Models\AkadRecord;
use App\Models\DeveloperPpjb;
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

class CreateAkadAction
{
    /** @param array<string, mixed> $data */
    public function handle(User $user, array $data): AkadRecord
    {
        Gate::forUser($user)->authorize('create', AkadRecord::class);

        return DB::transaction(function () use ($user, $data): AkadRecord {
            /** @var SalesCase $case */
            $case = SalesCase::whereKey($data['sales_case_id'] ?? null)->lockForUpdate()->firstOrFail();
            if ($user->isBranchScoped() && ! $user->belongsToBranch($case->branch_id)) {
                throw ValidationException::withMessages(['sales_case_id' => 'Sales case berada di luar cabang Anda.']);
            }
            if ($case->case_status !== SalesCaseStatus::Active || $case->akad()->exists()) {
                throw ValidationException::withMessages(['sales_case_id' => 'Sales case tidak aktif atau sudah memiliki Akad.']);
            }
            /** @var DeveloperPpjb|null $ppjb */
            $ppjb = DeveloperPpjb::query()->whereKey($data['developer_ppjb_id'] ?? null)->lockForUpdate()->first();
            if ($ppjb === null || $ppjb->sales_case_id !== $case->id || $ppjb->status !== DeveloperPpjbStatus::Active) {
                throw ValidationException::withMessages(['developer_ppjb_id' => 'PPJB Developer aktif tidak terkait dengan sales case ini.']);
            }
            try {
                /** @var AkadRecord $akad */
                $akad = AkadRecord::create([
                    'sales_case_id' => $case->id, 'developer_ppjb_id' => $ppjb->id,
                    'document_number' => $data['document_number'] ?? null, 'akad_date' => $data['akad_date'],
                    'akad_quality' => $data['akad_quality'] ?? null, 'notes' => $data['notes'] ?? null, 'created_by' => $user->id,
                ]);
            } catch (UniqueConstraintViolationException) {
                throw ValidationException::withMessages(['sales_case_id' => 'Sales case atau PPJB sudah memiliki Akad.']);
            }
            $case->advanceStageTo(SalesCaseStage::Bast);
            Unit::whereKey($case->unit_id)->update(['status' => UnitStatus::Terjual->value]);

            return $akad;
        });
    }
}
