<?php

namespace Tests\Feature;

use App\Actions\CancelPsjbAction;
use App\Actions\CompleteCashPemberkasanAction;
use App\Actions\CreateDocumentSubmissionAction;
use App\Actions\CreatePsjbAction;
use App\Actions\CreateSalesCaseAction;
use App\Actions\RecordBankResponseAction;
use App\Actions\RecordBiCheckAction;
use App\BankResponseType;
use App\BiCheckResult;
use App\DocumentSubmissionStatus;
use App\DocumentSubmissionType;
use App\FinancingType;
use App\Models\Bank;
use App\Models\BankProcess;
use App\Models\Branch;
use App\Models\Consumer;
use App\Models\DocumentSubmission;
use App\Models\Project;
use App\Models\Psjb;
use App\Models\SalesCase;
use App\Models\Unit;
use App\Models\User;
use App\SalesCaseStage;
use App\SalesCaseStatus;
use App\UnitStatus;
use App\UserRole;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class PhaseFourBankWorkflowTest extends TestCase
{
    use RefreshDatabase;

    private User $hq;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed();
        $this->hq = User::factory()->create();
        $this->hq->assignRole(UserRole::HqAdmin);
    }

    public function test_kpr_submission_requires_active_psjb_and_rejects_cash(): void
    {
        $kpr = $this->createCase(FinancingType::KprSubsidi);
        $bank = Bank::factory()->create();

        $this->expectValidation(fn () => $this->submit($kpr, $bank));

        $cash = $this->createCase(FinancingType::Cash);
        $this->prepareActivePsjb($cash);
        $this->expectValidation(fn () => $this->submit($cash, $bank));
    }

    public function test_submission_sequence_increments_and_history_is_preserved(): void
    {
        $case = $this->createCase(FinancingType::KprSubsidi);
        $this->prepareActivePsjb($case);

        $first = $this->submit($case, Bank::factory()->create());
        $second = $this->submit($case, Bank::factory()->create());
        $third = $this->submit($case, Bank::factory()->create());

        $this->assertSame([1, 2, 3], [$first->sequence, $second->sequence, $third->sequence]);
        $this->assertSame(3, $case->documentSubmissions()->count());
        $this->assertTrue($case->refresh()->current_stage === SalesCaseStage::ProsesBank);
        $this->assertTrue($first->refresh()->status === DocumentSubmissionStatus::Submitted);
    }

    public function test_multiple_responses_are_append_only_and_rejection_does_not_close_case(): void
    {
        $case = $this->preparedKprCase();
        $submission = $this->submit($case, Bank::factory()->create());

        $this->respond($case, $submission, BankResponseType::Process);
        $this->respond($case, $submission, BankResponseType::Revision);
        $this->respond($case, $submission, BankResponseType::Rejected);

        $this->assertSame(3, $submission->bankProcesses()->count());
        $this->assertTrue($case->refresh()->case_status === SalesCaseStatus::Active);
        $this->assertTrue($case->current_stage === SalesCaseStage::ProsesBank);
        $this->assertTrue($case->unit->status === UnitStatus::Booking);
        $this->assertNull($case->closed_at);
    }

    public function test_rejected_first_bank_then_approved_second_bank_preserves_all_history(): void
    {
        $case = $this->preparedKprCase();
        $btn = Bank::factory()->create(['name' => 'BTN']);
        $bri = Bank::factory()->create(['name' => 'BRI']);

        $btnSubmission = $this->submit($case, $btn);
        $this->respond($case, $btnSubmission, BankResponseType::Process);
        $this->respond($case, $btnSubmission, BankResponseType::Rejected);

        $briSubmission = $this->submit($case, $bri);
        $this->respond($case, $briSubmission, BankResponseType::Process);
        $approval = $this->respond($case, $briSubmission, BankResponseType::Approved, [
            'sp3k_number' => 'ABC123', 'sp3k_date' => '2026-09-20',
        ]);

        $this->assertSame(2, $case->documentSubmissions()->count());
        $this->assertSame(4, $case->bankProcesses()->count());
        $this->assertTrue($btnSubmission->refresh()->status === DocumentSubmissionStatus::Processing);
        $this->assertTrue($briSubmission->refresh()->status === DocumentSubmissionStatus::Closed);
        $this->assertTrue($approval->is_authoritative);
        $this->assertTrue($case->refresh()->current_stage === SalesCaseStage::PpjbDev);
        $this->assertTrue($case->case_status === SalesCaseStatus::Active);
        $this->assertTrue($case->unit->status === UnitStatus::Booking);
        $this->assertTrue($case->currentApprovedBankProcess->is($approval));
    }

    public function test_approval_requires_sp3k_and_second_authoritative_approval_is_rejected(): void
    {
        $case = $this->preparedKprCase();
        $first = $this->submit($case, Bank::factory()->create());
        $this->expectValidation(fn () => $this->respond($case, $first, BankResponseType::Approved));

        $this->respond($case, $first, BankResponseType::Approved, ['sp3k_number' => 'SP-1', 'sp3k_date' => '2026-09-20']);
        $second = $this->submit($case, Bank::factory()->create());
        $this->expectValidation(fn () => $this->respond($case, $second, BankResponseType::Approved, ['sp3k_number' => 'SP-2', 'sp3k_date' => '2026-09-21']));
    }

    public function test_authoritative_approval_is_structurally_unique(): void
    {
        $case = $this->preparedKprCase();
        $first = $this->submit($case, Bank::factory()->create());
        $second = $this->submit($case, Bank::factory()->create());
        $this->respond($case, $first, BankResponseType::Approved, ['sp3k_number' => 'A', 'sp3k_date' => '2026-09-20']);

        $this->expectException(UniqueConstraintViolationException::class);
        BankProcess::create([
            'sales_case_id' => $case->id, 'document_submission_id' => $second->id, 'bank_id' => $second->bank_id,
            'response_type' => BankResponseType::Approved, 'response_date' => '2026-09-21',
            'sp3k_number' => 'B', 'sp3k_date' => '2026-09-21', 'is_authoritative' => true,
        ]);
    }

    public function test_duplicate_sp3k_numbers_never_cross_link_cases(): void
    {
        $caseA = $this->preparedKprCase();
        $caseB = $this->preparedKprCase();
        $submissionA = $this->submit($caseA, Bank::factory()->create());
        $submissionB = $this->submit($caseB, Bank::factory()->create());

        $processA = $this->respond($caseA, $submissionA, BankResponseType::Approved, ['sp3k_number' => '123', 'sp3k_date' => '2026-09-20']);
        $processB = $this->respond($caseB, $submissionB, BankResponseType::Approved, ['sp3k_number' => '123', 'sp3k_date' => '2026-09-21']);

        $this->assertSame($caseA->id, $processA->sales_case_id);
        $this->assertSame($caseB->id, $processB->sales_case_id);
        $this->assertNotSame($processA->id, $processB->id);
        $this->assertSame(2, BankProcess::query()->where('sp3k_number', '123')->count());
    }

    public function test_response_rejects_submission_case_and_bank_forgery(): void
    {
        $caseA = $this->preparedKprCase();
        $caseB = $this->preparedKprCase();
        $submission = $this->submit($caseA, Bank::factory()->create());

        $this->expectValidation(fn () => $this->respond($caseB, $submission, BankResponseType::Process));

        $this->expectValidation(fn () => app(RecordBankResponseAction::class)->handle($this->hq, [
            'sales_case_id' => $caseA->id, 'document_submission_id' => $submission->id,
            'bank_id' => Bank::factory()->create()->id, 'response_type' => BankResponseType::Process,
            'response_date' => '2026-09-20',
        ]));
    }

    public function test_psjb_with_submission_cannot_be_cancelled(): void
    {
        $case = $this->preparedKprCase();
        $this->submit($case, Bank::factory()->create());

        $this->expectException(ValidationException::class);
        app(CancelPsjbAction::class)->handle($this->hq, $case->activePsjb()->firstOrFail());
    }

    public function test_cash_pemberkasan_creates_internal_submission_without_fake_bank_or_sp3k_records(): void
    {
        $case = $this->createCase(FinancingType::Cash);
        $this->prepareActivePsjb($case);

        $submission = app(CompleteCashPemberkasanAction::class)->handle($this->hq, $case);

        $this->assertTrue($case->refresh()->current_stage === SalesCaseStage::PpjbDev);
        $this->assertSame($submission->type, DocumentSubmissionType::CashInternal);
        $this->assertNull($submission->bank_id);
        $this->assertDatabaseCount('document_submissions', 1);
        $this->assertDatabaseCount('bank_processes', 0);
    }

    public function test_thirty_cash_cases_remain_independent_without_fake_processes(): void
    {
        for ($i = 0; $i < 30; $i++) {
            $case = $this->createCase(FinancingType::Cash);
            $this->prepareActivePsjb($case);
            app(CompleteCashPemberkasanAction::class)->handle($this->hq, $case);
        }

        $this->assertSame(30, SalesCase::query()->where('financing_type', FinancingType::Cash->value)->count());
        $this->assertDatabaseCount('document_submissions', 30);
        $this->assertDatabaseCount('bank_processes', 0);
    }

    public function test_branch_admin_cannot_forge_cross_branch_submission_or_response(): void
    {
        $case = $this->preparedKprCase();
        $submission = $this->submit($case, Bank::factory()->create());
        $otherBranch = Branch::factory()->create();
        $branchAdmin = User::factory()->for($otherBranch)->create();
        $branchAdmin->assignRole(UserRole::BranchAdmin);

        $this->expectValidation(fn () => app(CreateDocumentSubmissionAction::class)->handle($branchAdmin, [
            'sales_case_id' => $case->id, 'bank_id' => Bank::factory()->create()->id, 'submission_date' => '2026-09-20',
        ]));

        $this->expectValidation(fn () => app(RecordBankResponseAction::class)->handle($branchAdmin, [
            'sales_case_id' => $case->id, 'document_submission_id' => $submission->id, 'bank_id' => $submission->bank_id,
            'response_type' => BankResponseType::Process, 'response_date' => '2026-09-20',
        ]));
    }

    public function test_stage_does_not_regress_after_approval(): void
    {
        $case = $this->preparedKprCase();
        $submission = $this->submit($case, Bank::factory()->create());
        $this->respond($case, $submission, BankResponseType::Approved, ['sp3k_number' => 'APP', 'sp3k_date' => '2026-09-20']);
        $this->respond($case, $submission, BankResponseType::Revision);

        $this->assertTrue($case->refresh()->current_stage === SalesCaseStage::PpjbDev);
    }

    public function test_submission_and_process_identity_cannot_be_updated_by_policy(): void
    {
        $case = $this->preparedKprCase();
        $submission = $this->submit($case, Bank::factory()->create());
        $process = $this->respond($case, $submission, BankResponseType::Process);

        $this->assertFalse($this->hq->can('update', $submission));
        $this->assertFalse($this->hq->can('update', $process));
    }

    private function createCase(FinancingType $type): SalesCase
    {
        $branch = Branch::factory()->create();
        $unit = Unit::factory()->for(Project::factory()->for($branch))->create();

        return app(CreateSalesCaseAction::class)->handle($this->hq, [
            'unit_id' => $unit->id, 'financing_type' => $type, 'consumer_id' => Consumer::factory()->create()->id,
        ]);
    }

    private function prepareActivePsjb(SalesCase $case): Psjb
    {
        if ($case->financing_type === FinancingType::KprSubsidi) {
            app(RecordBiCheckAction::class)->handle($this->hq, [
                'sales_case_id' => $case->id, 'check_date' => '2026-09-01', 'result' => BiCheckResult::Clear,
            ]);
        }

        return app(CreatePsjbAction::class)->handle($this->hq, ['sales_case_id' => $case->id, 'psjb_date' => '2026-09-02']);
    }

    private function preparedKprCase(): SalesCase
    {
        $case = $this->createCase(FinancingType::KprSubsidi);
        $this->prepareActivePsjb($case);

        return $case;
    }

    private function submit(SalesCase $case, Bank $bank): DocumentSubmission
    {
        return app(CreateDocumentSubmissionAction::class)->handle($this->hq, [
            'sales_case_id' => $case->id, 'bank_id' => $bank->id, 'submission_date' => '2026-09-10',
        ]);
    }

    /** @param array<string, mixed> $extra */
    private function respond(SalesCase $case, DocumentSubmission $submission, BankResponseType $type, array $extra = []): BankProcess
    {
        return app(RecordBankResponseAction::class)->handle($this->hq, [
            'sales_case_id' => $case->id, 'document_submission_id' => $submission->id,
            'bank_id' => $submission->bank_id, 'response_type' => $type, 'response_date' => '2026-09-15', ...$extra,
        ]);
    }

    private function expectValidation(callable $callback): void
    {
        try {
            $callback();
            $this->fail('Expected validation exception.');
        } catch (ValidationException) {
            $this->assertTrue(true);
        }
    }
}
