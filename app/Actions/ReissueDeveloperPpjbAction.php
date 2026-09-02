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

class ReissueDeveloperPpjbAction
{
    /** @param array<string, mixed> $data */
    public function handle(User $user, SalesCase $case, array $data): DeveloperPpjb
    {
        Gate::forUser($user)->authorize('create', DeveloperPpjb::class);

        return DB::transaction(function () use ($user, $case, $data): DeveloperPpjb {
            /** @var SalesCase $case */
            $case = SalesCase::whereKey($case->id)->lockForUpdate()->firstOrFail();
            if ($case->case_status !== SalesCaseStatus::Active || $case->akad()->exists()) {
                throw ValidationException::withMessages(['sales_case_id' => 'PPJB tidak dapat di-reissue setelah Akad atau case ditutup.']);
            }
            /** @var DeveloperPpjb|null $old */
            $old = $case->activeDeveloperPpjb()->lockForUpdate()->first();
            if ($old === null) {
                throw ValidationException::withMessages(['sales_case_id' => 'Tidak ada PPJB Developer aktif.']);
            }
            $old->update(['status' => DeveloperPpjbStatus::Superseded]);
            /** @var DeveloperPpjb $new */
            $new = DeveloperPpjb::create([
                'sales_case_id' => $case->id, 'bank_process_id' => $old->bank_process_id,
                'document_number' => $data['document_number'] ?? null, 'document_date' => $data['document_date'],
                'status' => DeveloperPpjbStatus::Active, 'notes' => $data['notes'] ?? null, 'created_by' => $user->id,
            ]);
            $case->advanceStageTo(SalesCaseStage::Akad);

            return $new;
        });
    }
}
