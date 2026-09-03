<?php

namespace App\Actions;

use App\BankResponseType;
use App\DocumentSubmissionStatus;
use App\DocumentSubmissionType;
use App\FinancingType;
use App\Models\BankProcess;
use App\Models\DocumentSubmission;
use App\Models\SalesCase;
use App\Models\User;
use App\SalesCaseStage;
use App\SalesCaseStatus;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\ValidationException;

class RecordBankResponseAction
{
    /**
     * @param  array<string, mixed>  $data
     */
    public function handle(User $user, array $data): BankProcess
    {
        Gate::forUser($user)->authorize('create', BankProcess::class);

        return DB::transaction(function () use ($user, $data): BankProcess {
            /** @var SalesCase $case */
            $case = SalesCase::whereKey($data['sales_case_id'] ?? null)->lockForUpdate()->firstOrFail();

            if ($user->isBranchScoped() && ! $user->belongsToBranch($case->branch_id)) {
                throw ValidationException::withMessages(['sales_case_id' => 'Sales case berada di luar cabang Anda.']);
            }

            if ($case->case_status !== SalesCaseStatus::Active) {
                throw ValidationException::withMessages(['sales_case_id' => 'Sales case tidak aktif.']);
            }

            if ($case->financing_type !== FinancingType::KprSubsidi) {
                throw ValidationException::withMessages(['sales_case_id' => 'Bank response hanya untuk KPR Subsidi.']);
            }

            /** @var DocumentSubmission|null $submission */
            $submission = DocumentSubmission::query()
                ->whereKey($data['document_submission_id'] ?? null)
                ->lockForUpdate()
                ->first();

            if ($submission === null || $submission->sales_case_id !== $case->id) {
                throw ValidationException::withMessages(['document_submission_id' => 'Submission tidak terkait dengan sales case ini.']);
            }

            if ($submission->type === DocumentSubmissionType::CashInternal) {
                throw ValidationException::withMessages(['document_submission_id' => 'Pemberkasan CASH tidak dapat menerima response bank.']);
            }

            if (($data['bank_id'] ?? null) !== $submission->bank_id) {
                throw ValidationException::withMessages(['bank_id' => 'Bank harus sama dengan bank pada submission.']);
            }

            $responseType = $data['response_type'] instanceof BankResponseType
                ? $data['response_type']
                : BankResponseType::from($data['response_type']);

            if ($responseType === BankResponseType::Approved) {
                if (blank($data['sp3k_number'] ?? null) || blank($data['sp3k_date'] ?? null)) {
                    throw ValidationException::withMessages([
                        'sp3k_number' => 'Nomor dan tanggal SP3K wajib untuk approval.',
                    ]);
                }

                if ($case->currentApprovedBankProcess()->exists()) {
                    throw ValidationException::withMessages(['response_type' => 'Sales case sudah memiliki approval bank authoritative.']);
                }
            }

            try {
                /** @var BankProcess $process */
                $process = BankProcess::create([
                    'sales_case_id' => $case->id,
                    'document_submission_id' => $submission->id,
                    'bank_id' => $submission->bank_id,
                    'response_type' => $responseType,
                    'response_date' => $data['response_date'],
                    'sp3k_number' => $responseType === BankResponseType::Approved ? $data['sp3k_number'] : null,
                    'sp3k_date' => $responseType === BankResponseType::Approved ? $data['sp3k_date'] : null,
                    'credit_limit' => $data['credit_limit'] ?? null,
                    'tenor' => $data['tenor'] ?? null,
                    'is_authoritative' => $responseType === BankResponseType::Approved,
                    'notes' => $data['notes'] ?? null,
                    'created_by' => $user->id,
                ]);
            } catch (UniqueConstraintViolationException) {
                throw ValidationException::withMessages(['response_type' => 'Sales case sudah memiliki approval bank authoritative.']);
            }

            if ($responseType === BankResponseType::Approved) {
                $submission->update(['status' => DocumentSubmissionStatus::Closed]);
                $case->advanceStageTo(SalesCaseStage::PpjbDev);
            } else {
                if ($submission->status === DocumentSubmissionStatus::Submitted) {
                    $submission->update(['status' => DocumentSubmissionStatus::Processing]);
                }

                $case->advanceStageTo(SalesCaseStage::ProsesBank);
            }

            return $process;
        });
    }
}
