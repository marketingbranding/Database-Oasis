<?php

namespace App\Actions;

use App\DeveloperPpjbStatus;
use App\DocumentSubmissionStatus;
use App\DocumentSubmissionType;
use App\FinancingType;
use App\Models\BankProcess;
use App\Models\DeveloperPpjb;
use App\Models\SalesCase;
use App\Models\User;
use App\SalesCaseStage;
use App\SalesCaseStatus;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\ValidationException;

class CreateDeveloperPpjbAction
{
    /** @param array<string, mixed> $data */
    public function handle(User $user, array $data): DeveloperPpjb
    {
        Gate::forUser($user)->authorize('create', DeveloperPpjb::class);

        return DB::transaction(function () use ($user, $data): DeveloperPpjb {
            /** @var SalesCase $case */
            $case = SalesCase::whereKey($data['sales_case_id'] ?? null)->lockForUpdate()->firstOrFail();
            $this->validateCase($user, $case);

            if ($case->activeDeveloperPpjb()->exists()) {
                throw ValidationException::withMessages(['sales_case_id' => 'Sales case sudah memiliki PPJB Developer aktif.']);
            }

            $bankProcessId = null;
            if ($case->financing_type === FinancingType::KprSubsidi) {
                /** @var BankProcess|null $approval */
                $approval = $case->currentApprovedBankProcess()->lockForUpdate()->first();
                if ($approval === null || blank($approval->sp3k_number) || $approval->sp3k_date === null) {
                    throw ValidationException::withMessages(['sales_case_id' => 'KPR memerlukan approval bank authoritative dengan SP3K valid.']);
                }
                $bankProcessId = $approval->id;
            } else {
                $hasCashPemberkasan = $case->documentSubmissions()
                    ->where('type', DocumentSubmissionType::CashInternal->value)
                    ->whereNotIn('status', [DocumentSubmissionStatus::Cancelled->value])
                    ->exists();

                if (! $case->activePsjb()->exists() || ! $hasCashPemberkasan || ! $case->current_stage->isBeyond(SalesCaseStage::Pemberkasan)) {
                    throw ValidationException::withMessages(['sales_case_id' => 'CASH memerlukan PSJB aktif dan Pemberkasan CASH selesai sebelum PPJB.']);
                }
            }

            try {
                /** @var DeveloperPpjb $ppjb */
                $ppjb = DeveloperPpjb::create([
                    'sales_case_id' => $case->id, 'bank_process_id' => $bankProcessId,
                    'document_number' => $data['document_number'] ?? null, 'document_date' => $data['document_date'],
                    'status' => DeveloperPpjbStatus::Active, 'notes' => $data['notes'] ?? null, 'created_by' => $user->id,
                ]);
            } catch (UniqueConstraintViolationException) {
                throw ValidationException::withMessages(['sales_case_id' => 'Sales case sudah memiliki PPJB Developer aktif.']);
            }

            $case->advanceStageTo(SalesCaseStage::Akad);

            return $ppjb;
        });
    }

    private function validateCase(User $user, SalesCase $case): void
    {
        if ($user->isBranchScoped() && ! $user->belongsToBranch($case->branch_id)) {
            throw ValidationException::withMessages(['sales_case_id' => 'Sales case berada di luar cabang Anda.']);
        }
        if ($case->case_status !== SalesCaseStatus::Active) {
            throw ValidationException::withMessages(['sales_case_id' => 'Sales case tidak aktif.']);
        }
    }
}
