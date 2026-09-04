<?php

namespace Tests\Feature;

use App\BankResponseType;
use App\BiCheckResult;
use App\DeveloperPpjbStatus;
use App\DocumentSubmissionStatus;
use App\DocumentSubmissionType;
use App\Models\Bank;
use App\Models\BankProcess;
use App\Models\BiCheck;
use App\Models\DeveloperPpjb;
use App\Models\DocumentSubmission;
use App\Models\Psjb;
use App\Models\SalesCase;
use App\PsjbStatus;
use Illuminate\Database\QueryException;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class PhaseEightCNullableDateConstraintTest extends TestCase
{
    use RefreshDatabase;

    public function test_legacy_intermediate_records_may_store_null_business_dates_with_explicit_flags(): void
    {
        $case = SalesCase::factory()->create();
        $bank = Bank::factory()->create();

        $bi = BiCheck::create([
            'sales_case_id' => $case->id,
            'check_date' => null,
            'result' => BiCheckResult::Review,
            'is_legacy_import' => true,
            'legacy_date_missing' => true,
        ]);
        $psjb = Psjb::create([
            'sales_case_id' => $case->id,
            'psjb_date' => null,
            'status' => PsjbStatus::Active,
            'is_legacy_import' => true,
            'legacy_date_missing' => true,
        ]);
        $submission = DocumentSubmission::create([
            'sales_case_id' => $case->id,
            'psjb_id' => $psjb->id,
            'bank_id' => $bank->id,
            'submission_date' => null,
            'sequence' => 1,
            'status' => DocumentSubmissionStatus::Submitted,
            'type' => DocumentSubmissionType::Bank,
            'is_legacy_import' => true,
            'legacy_date_missing' => true,
        ]);
        $process = BankProcess::create([
            'sales_case_id' => $case->id,
            'document_submission_id' => $submission->id,
            'bank_id' => $bank->id,
            'response_type' => BankResponseType::Process,
            'response_date' => null,
            'is_authoritative' => false,
            'is_legacy_import' => true,
            'legacy_date_missing' => true,
        ]);
        $ppjb = DeveloperPpjb::create([
            'sales_case_id' => $case->id,
            'document_date' => null,
            'status' => DeveloperPpjbStatus::Active,
            'is_legacy_import' => true,
            'legacy_date_missing' => true,
        ]);

        $this->assertNull($bi->check_date);
        $this->assertNull($psjb->psjb_date);
        $this->assertNull($submission->submission_date);
        $this->assertNull($process->response_date);
        $this->assertNull($ppjb->document_date);
    }

    public function test_non_legacy_bi_date_cannot_be_null(): void
    {
        $this->expectException(QueryException::class);
        BiCheck::create([
            'sales_case_id' => SalesCase::factory()->create()->id,
            'check_date' => null,
            'result' => BiCheckResult::Review,
            'is_legacy_import' => false,
            'legacy_date_missing' => false,
        ]);
    }

    public function test_non_legacy_psjb_date_cannot_be_null(): void
    {
        $this->expectException(QueryException::class);
        Psjb::create([
            'sales_case_id' => SalesCase::factory()->create()->id,
            'psjb_date' => null,
            'status' => PsjbStatus::Active,
            'is_legacy_import' => false,
            'legacy_date_missing' => false,
        ]);
    }

    public function test_non_legacy_submission_date_cannot_be_null(): void
    {
        $case = SalesCase::factory()->create();
        $this->expectException(QueryException::class);
        DocumentSubmission::create([
            'sales_case_id' => $case->id,
            'bank_id' => Bank::factory()->create()->id,
            'submission_date' => null,
            'sequence' => 1,
            'status' => DocumentSubmissionStatus::Submitted,
            'type' => DocumentSubmissionType::Bank,
            'is_legacy_import' => false,
            'legacy_date_missing' => false,
        ]);
    }

    public function test_non_legacy_bank_response_date_cannot_be_null(): void
    {
        $case = SalesCase::factory()->create();
        $this->expectException(QueryException::class);
        BankProcess::create([
            'sales_case_id' => $case->id,
            'response_type' => BankResponseType::Process,
            'response_date' => null,
            'is_authoritative' => false,
            'is_legacy_import' => false,
            'legacy_date_missing' => false,
        ]);
    }

    public function test_non_legacy_ppjb_date_cannot_be_null(): void
    {
        $this->expectException(QueryException::class);
        DeveloperPpjb::create([
            'sales_case_id' => SalesCase::factory()->create()->id,
            'document_date' => null,
            'status' => DeveloperPpjbStatus::Active,
            'is_legacy_import' => false,
            'legacy_date_missing' => false,
        ]);
    }

    public function test_psjb_active_partial_unique_index_survives_nullable_migration(): void
    {
        $case = SalesCase::factory()->create();
        $first = Psjb::factory()->for($case)->create(['status' => PsjbStatus::Active]);

        $violated = false;
        try {
            DB::transaction(fn () => Psjb::factory()->for($case)->create(['status' => PsjbStatus::Active]));
        } catch (UniqueConstraintViolationException) {
            $violated = true;
        }
        $this->assertTrue($violated);
        $this->assertSame(1, Psjb::query()->where('sales_case_id', $case->id)->count());

        $first->update(['status' => PsjbStatus::Superseded]);
        Psjb::factory()->for($case)->create(['status' => PsjbStatus::Active]);
        $this->assertSame(2, Psjb::query()->where('sales_case_id', $case->id)->count());
    }

    public function test_bank_authoritative_partial_unique_index_survives_nullable_migration(): void
    {
        $case = SalesCase::factory()->create();
        $bank = Bank::factory()->create();
        $first = BankProcess::create([
            'sales_case_id' => $case->id,
            'bank_id' => $bank->id,
            'response_type' => BankResponseType::Approved,
            'response_date' => '2026-01-01',
            'sp3k_number' => 'SP3K-A',
            'sp3k_date' => '2026-01-01',
            'is_authoritative' => true,
        ]);

        $violated = false;
        try {
            DB::transaction(fn () => BankProcess::create([
                'sales_case_id' => $case->id,
                'bank_id' => $bank->id,
                'response_type' => BankResponseType::Approved,
                'response_date' => '2026-01-02',
                'sp3k_number' => 'SP3K-B',
                'sp3k_date' => '2026-01-02',
                'is_authoritative' => true,
            ]));
        } catch (UniqueConstraintViolationException) {
            $violated = true;
        }
        $this->assertTrue($violated);
        $this->assertSame(1, BankProcess::query()->where('sales_case_id', $case->id)->count());

        $first->update(['is_authoritative' => false]);
        BankProcess::create([
            'sales_case_id' => $case->id,
            'bank_id' => $bank->id,
            'response_type' => BankResponseType::Approved,
            'response_date' => '2026-01-02',
            'sp3k_number' => 'SP3K-B',
            'sp3k_date' => '2026-01-02',
            'is_authoritative' => true,
        ]);
        $this->assertSame(2, BankProcess::query()->where('sales_case_id', $case->id)->count());
    }

    public function test_ppjb_active_partial_unique_index_survives_nullable_migration(): void
    {
        $case = SalesCase::factory()->create();
        $first = DeveloperPpjb::factory()->for($case)->create(['status' => DeveloperPpjbStatus::Active]);

        $violated = false;
        try {
            DB::transaction(fn () => DeveloperPpjb::factory()->for($case)->create(['status' => DeveloperPpjbStatus::Active]));
        } catch (UniqueConstraintViolationException) {
            $violated = true;
        }
        $this->assertTrue($violated);
        $this->assertSame(1, DeveloperPpjb::query()->where('sales_case_id', $case->id)->count());

        $first->update(['status' => DeveloperPpjbStatus::Superseded]);
        DeveloperPpjb::factory()->for($case)->create(['status' => DeveloperPpjbStatus::Active]);
        $this->assertSame(2, DeveloperPpjb::query()->where('sales_case_id', $case->id)->count());
    }

    public function test_submission_sequence_unique_index_survives_nullable_migration(): void
    {
        $case = SalesCase::factory()->create();
        $bank = Bank::factory()->create();
        DocumentSubmission::create([
            'sales_case_id' => $case->id,
            'bank_id' => $bank->id,
            'submission_date' => '2026-01-01',
            'sequence' => 1,
            'status' => DocumentSubmissionStatus::Submitted,
            'type' => DocumentSubmissionType::Bank,
        ]);

        $this->expectException(UniqueConstraintViolationException::class);
        DocumentSubmission::create([
            'sales_case_id' => $case->id,
            'bank_id' => $bank->id,
            'submission_date' => '2026-01-02',
            'sequence' => 1,
            'status' => DocumentSubmissionStatus::Submitted,
            'type' => DocumentSubmissionType::Bank,
        ]);
    }
}
