<?php

namespace App\Actions;

use App\DocumentSubmissionStatus;
use App\FinancingType;
use App\Models\Bank;
use App\Models\DocumentSubmission;
use App\Models\Psjb;
use App\Models\SalesCase;
use App\Models\User;
use App\PsjbStatus;
use App\SalesCaseStage;
use App\SalesCaseStatus;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\ValidationException;

class CreateDocumentSubmissionAction
{
    /**
     * @param  array<string, mixed>  $data
     */
    public function handle(User $user, array $data): DocumentSubmission
    {
        Gate::forUser($user)->authorize('create', DocumentSubmission::class);

        return DB::transaction(function () use ($user, $data): DocumentSubmission {
            /** @var SalesCase $case */
            $case = SalesCase::whereKey($data['sales_case_id'] ?? null)->lockForUpdate()->firstOrFail();

            if ($user->isBranchScoped() && ! $user->belongsToBranch($case->branch_id)) {
                throw ValidationException::withMessages(['sales_case_id' => 'Sales case berada di luar cabang Anda.']);
            }

            if ($case->case_status !== SalesCaseStatus::Active) {
                throw ValidationException::withMessages(['sales_case_id' => 'Sales case tidak aktif.']);
            }

            if ($case->financing_type !== FinancingType::KprSubsidi) {
                throw ValidationException::withMessages(['sales_case_id' => 'Pemberkasan bank hanya untuk KPR Subsidi.']);
            }

            /** @var Psjb|null $psjb */
            $psjb = Psjb::query()
                ->where('sales_case_id', $case->id)
                ->where('status', PsjbStatus::Active->value)
                ->lockForUpdate()
                ->first();

            if ($psjb === null) {
                throw ValidationException::withMessages(['sales_case_id' => 'Sales case tidak memiliki PSJB aktif.']);
            }

            /** @var Bank|null $bank */
            $bank = Bank::query()->whereKey($data['bank_id'] ?? null)->where('is_active', true)->first();

            if ($bank === null) {
                throw ValidationException::withMessages(['bank_id' => 'Bank tidak valid atau tidak aktif.']);
            }

            $sequence = ((int) DocumentSubmission::query()
                ->withTrashed()
                ->where('sales_case_id', $case->id)
                ->max('sequence')) + 1;

            try {
                /** @var DocumentSubmission $submission */
                $submission = DocumentSubmission::create([
                    'sales_case_id' => $case->id,
                    'psjb_id' => $psjb->id,
                    'bank_id' => $bank->id,
                    'submission_date' => $data['submission_date'],
                    'bank_branch' => $data['bank_branch'] ?? null,
                    'sequence' => $sequence,
                    'status' => DocumentSubmissionStatus::Submitted,
                    'notes' => $data['notes'] ?? null,
                    'created_by' => $user->id,
                ]);
            } catch (UniqueConstraintViolationException) {
                throw ValidationException::withMessages(['sales_case_id' => 'Nomor urut submission bentrok. Silakan ulangi.']);
            }

            $case->advanceStageTo(SalesCaseStage::ProsesBank);

            return $submission;
        });
    }
}
