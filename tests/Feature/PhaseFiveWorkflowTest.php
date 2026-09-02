<?php

namespace Tests\Feature;

use App\Actions\AdvanceCashCaseToPpjbAction;
use App\Actions\CancelDeveloperPpjbAction;
use App\Actions\CancelSalesCaseAction;
use App\Actions\CreateAkadAction;
use App\Actions\CreateBastAction;
use App\Actions\CreateDeveloperPpjbAction;
use App\Actions\CreateDocumentSubmissionAction;
use App\Actions\CreatePsjbAction;
use App\Actions\CreateSalesCaseAction;
use App\Actions\MarkSalesCaseMundurAction;
use App\Actions\MarkSalesCaseRejectedAction;
use App\Actions\MoveSalesCaseUnitAction;
use App\Actions\RecordBankResponseAction;
use App\Actions\RecordBiCheckAction;
use App\Actions\ReissueDeveloperPpjbAction;
use App\BankResponseType;
use App\BiCheckResult;
use App\DeveloperPpjbStatus;
use App\FinancingType;
use App\Models\AkadRecord;
use App\Models\Bank;
use App\Models\BankProcess;
use App\Models\BastRecord;
use App\Models\Branch;
use App\Models\Consumer;
use App\Models\DeveloperPpjb;
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
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class PhaseFiveWorkflowTest extends TestCase
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

    public function test_kpr_ppjb_requires_authoritative_approval_and_resolves_it_server_side(): void
    {
        $case = $this->baseCase(FinancingType::KprSubsidi);
        $this->expectValidation(fn () => $this->createPpjb($case));
        $approval = $this->approveKpr($case);

        $ppjb = app(CreateDeveloperPpjbAction::class)->handle($this->hq, [
            'sales_case_id' => $case->id, 'bank_process_id' => BankProcess::factory()->create()->id,
            'document_date' => '2026-09-21', 'document_number' => 'PPJB-1',
        ]);

        $this->assertSame($approval->id, $ppjb->bank_process_id);
        $this->assertTrue($case->refresh()->current_stage === SalesCaseStage::Akad);
    }

    public function test_cash_ppjb_requires_cash_advance_and_has_null_bank_process(): void
    {
        $case = $this->baseCase(FinancingType::Cash);
        $this->preparePsjb($case);
        $this->expectValidation(fn () => $this->createPpjb($case));
        app(AdvanceCashCaseToPpjbAction::class)->handle($this->hq, $case);

        $ppjb = $this->createPpjb($case);
        $this->assertNull($ppjb->bank_process_id);
        $this->assertDatabaseCount('bank_processes', 0);
        $this->assertDatabaseCount('document_submissions', 0);
    }

    public function test_ppjb_reissue_and_cancel_preserve_history_before_akad(): void
    {
        $case = $this->cashReadyCase();
        $old = $this->createPpjb($case, 'OLD');
        $new = app(ReissueDeveloperPpjbAction::class)->handle($this->hq, $case, ['document_date' => '2026-09-22', 'document_number' => 'NEW']);

        $this->assertTrue($old->refresh()->status === DeveloperPpjbStatus::Superseded);
        $this->assertTrue($new->status === DeveloperPpjbStatus::Active);
        $this->assertNotSame($old->id, $new->id);
        $cancelled = app(CancelDeveloperPpjbAction::class)->handle($this->hq, $new);
        $this->assertTrue($cancelled->status === DeveloperPpjbStatus::Cancelled);
        $this->assertTrue($case->refresh()->current_stage === SalesCaseStage::PpjbDev);
    }

    public function test_only_one_active_ppjb_and_one_akad_and_bast_are_structurally_enforced(): void
    {
        $case = $this->cashReadyCase();
        $ppjb = $this->createPpjb($case);
        $this->expectException(UniqueConstraintViolationException::class);
        DeveloperPpjb::create(['sales_case_id' => $case->id, 'document_date' => '2026-09-22', 'status' => DeveloperPpjbStatus::Active]);
    }

    public function test_akad_and_bast_uniqueness_are_structurally_enforced(): void
    {
        $case = $this->cashReadyCase();
        $ppjb = $this->createPpjb($case);
        $akad = $this->createAkad($case, $ppjb);

        $this->expectUniqueConstraint(fn () => AkadRecord::create([
            'sales_case_id' => $case->id, 'developer_ppjb_id' => DeveloperPpjb::factory()->create()->id, 'akad_date' => '2026-09-26',
        ]));

        $this->createBast($case, $akad);
        $this->expectUniqueConstraint(fn () => BastRecord::create([
            'sales_case_id' => $case->id, 'akad_id' => $akad->id, 'bast_date' => '2026-10-01', 'status' => 'COMPLETED',
        ]));
    }

    public function test_branch_admin_cannot_forge_cross_branch_ppjb_akad_or_bast(): void
    {
        $case = $this->cashReadyCase();
        $ppjb = $this->createPpjb($case);
        $akad = $this->createAkad($case, $ppjb);
        $branchAdmin = User::factory()->for(Branch::factory()->create())->create();
        $branchAdmin->assignRole(UserRole::BranchAdmin);

        $this->expectValidation(fn () => app(CreateDeveloperPpjbAction::class)->handle($branchAdmin, ['sales_case_id' => $case->id, 'document_date' => '2026-09-21']));
        $this->expectValidation(fn () => app(CreateAkadAction::class)->handle($branchAdmin, ['sales_case_id' => $case->id, 'developer_ppjb_id' => $ppjb->id, 'akad_date' => '2026-09-25']));
        $this->expectValidation(fn () => app(CreateBastAction::class)->handle($branchAdmin, ['sales_case_id' => $case->id, 'akad_id' => $akad->id, 'bast_date' => '2026-09-30']));
    }

    public function test_akad_requires_matching_active_ppjb_and_changes_unit_to_terjual(): void
    {
        $caseA = $this->cashReadyCase();
        $caseB = $this->cashReadyCase();
        $ppjbA = $this->createPpjb($caseA);
        $ppjbB = $this->createPpjb($caseB);
        $this->expectValidation(fn () => $this->createAkad($caseA, $ppjbB));

        $akad = $this->createAkad($caseA, $ppjbA, 'AKAD-X');
        $this->assertSame($ppjbA->id, $akad->developer_ppjb_id);
        $this->assertTrue($caseA->refresh()->current_stage === SalesCaseStage::Bast);
        $this->assertTrue($caseA->case_status === SalesCaseStatus::Active);
        $this->assertTrue($caseA->unit->status === UnitStatus::Terjual);
        $this->expectValidation(fn () => $this->createAkad($caseA, $ppjbA));
    }

    public function test_post_akad_closing_moving_reissue_and_cancel_are_blocked(): void
    {
        $case = $this->cashReadyCase();
        $ppjb = $this->createPpjb($case);
        $this->createAkad($case, $ppjb);

        $this->expectValidation(fn () => app(MarkSalesCaseMundurAction::class)->handle($this->hq, $case, 'x'));
        $this->expectValidation(fn () => app(MarkSalesCaseRejectedAction::class)->handle($this->hq, $case, 'x'));
        $this->expectValidation(fn () => app(CancelSalesCaseAction::class)->handle($this->hq, $case, 'x'));
        $this->expectValidation(fn () => app(MoveSalesCaseUnitAction::class)->handle($this->hq, $case, Unit::factory()->for(Project::factory()->for($case->branch))->create()->id, 'x'));
        $this->expectValidation(fn () => app(ReissueDeveloperPpjbAction::class)->handle($this->hq, $case, ['document_date' => '2026-09-23']));
        $this->expectValidation(fn () => app(CancelDeveloperPpjbAction::class)->handle($this->hq, $ppjb));
    }

    public function test_bast_requires_matching_akad_and_completes_case(): void
    {
        $caseA = $this->cashReadyCase();
        $caseB = $this->cashReadyCase();
        $akadA = $this->createAkad($caseA, $this->createPpjb($caseA));
        $akadB = $this->createAkad($caseB, $this->createPpjb($caseB));
        $this->expectValidation(fn () => $this->createBast($caseA, $akadB));

        $bast = $this->createBast($caseA, $akadA, 'BAST-X');
        $caseA->refresh();
        $this->assertSame($akadA->id, $bast->akad_id);
        $this->assertTrue($caseA->case_status === SalesCaseStatus::Completed);
        $this->assertTrue($caseA->current_stage === SalesCaseStage::Completed);
        $this->assertNotNull($caseA->closed_at);
        $this->assertTrue($caseA->unit->status === UnitStatus::Terjual);
        $this->expectValidation(fn () => $this->createBast($caseA, $akadA));
    }

    public function test_duplicate_document_numbers_do_not_cross_link(): void
    {
        $caseA = $this->cashReadyCase();
        $caseB = $this->cashReadyCase();
        $ppjbA = $this->createPpjb($caseA, '578/MHT');
        $ppjbB = $this->createPpjb($caseB, '578/MHT');
        $akadA = $this->createAkad($caseA, $ppjbA, 'AKAD-DUP');
        $akadB = $this->createAkad($caseB, $ppjbB, 'AKAD-DUP');
        $bastA = $this->createBast($caseA, $akadA, 'BAST-DUP');
        $bastB = $this->createBast($caseB, $akadB, 'BAST-DUP');

        $this->assertSame($ppjbA->id, $akadA->developer_ppjb_id);
        $this->assertSame($ppjbB->id, $akadB->developer_ppjb_id);
        $this->assertSame($akadA->id, $bastA->akad_id);
        $this->assertSame($akadB->id, $bastB->akad_id);
    }

    public function test_reissued_ppjb_is_the_one_used_by_akad_and_becomes_immutable(): void
    {
        $case = $this->cashReadyCase();
        $old = $this->createPpjb($case, 'A');
        $new = app(ReissueDeveloperPpjbAction::class)->handle($this->hq, $case, ['document_date' => '2026-09-22', 'document_number' => 'B']);
        $akad = $this->createAkad($case, $new);

        $this->assertSame($new->id, $akad->developer_ppjb_id);
        $this->assertTrue($old->refresh()->status === DeveloperPpjbStatus::Superseded);
        $this->expectValidation(fn () => app(CancelDeveloperPpjbAction::class)->handle($this->hq, $new));
    }

    public function test_cash_end_to_end_multiple_cases_have_no_fake_bank_identity(): void
    {
        for ($i = 0; $i < 5; $i++) {
            $case = $this->cashReadyCase();
            $ppjb = $this->createPpjb($case, 'CASH-DOC');
            $this->assertNull($ppjb->bank_process_id);
            $akad = $this->createAkad($case, $ppjb, 'CASH-AKAD');
            $this->createBast($case, $akad, 'CASH-BAST');
            $this->assertTrue($case->refresh()->case_status === SalesCaseStatus::Completed);
        }
        $this->assertDatabaseCount('bank_processes', 0);
        $this->assertDatabaseCount('document_submissions', 0);
        $this->assertSame(5, DeveloperPpjb::query()->count());
    }

    public function test_kpr_end_to_end_preserves_explicit_chain(): void
    {
        $case = $this->baseCase(FinancingType::KprSubsidi);
        $approval = $this->approveKpr($case);
        $ppjb = $this->createPpjb($case);
        $akad = $this->createAkad($case, $ppjb);
        $this->assertTrue($case->refresh()->case_status === SalesCaseStatus::Active);
        $bast = $this->createBast($case, $akad);

        $this->assertSame($approval->id, $ppjb->bank_process_id);
        $this->assertSame($case->id, $ppjb->sales_case_id);
        $this->assertSame($ppjb->id, $akad->developer_ppjb_id);
        $this->assertSame($akad->id, $bast->akad_id);
        $this->assertTrue($case->refresh()->case_status === SalesCaseStatus::Completed);
    }

    private function baseCase(FinancingType $type): SalesCase
    {
        $branch = Branch::factory()->create();
        $unit = Unit::factory()->for(Project::factory()->for($branch))->create();

        return app(CreateSalesCaseAction::class)->handle($this->hq, ['unit_id' => $unit->id, 'consumer_id' => Consumer::factory()->create()->id, 'financing_type' => $type]);
    }

    private function preparePsjb(SalesCase $case): Psjb
    {
        app(RecordBiCheckAction::class)->handle($this->hq, ['sales_case_id' => $case->id, 'check_date' => '2026-09-01', 'result' => BiCheckResult::Clear]);

        return app(CreatePsjbAction::class)->handle($this->hq, ['sales_case_id' => $case->id, 'psjb_date' => '2026-09-02']);
    }

    private function cashReadyCase(): SalesCase
    {
        $case = $this->baseCase(FinancingType::Cash);
        $this->preparePsjb($case);
        app(AdvanceCashCaseToPpjbAction::class)->handle($this->hq, $case);

        return $case;
    }

    private function approveKpr(SalesCase $case): BankProcess
    {
        $this->preparePsjb($case);
        $bank = Bank::factory()->create();
        $submission = app(CreateDocumentSubmissionAction::class)->handle($this->hq, ['sales_case_id' => $case->id, 'bank_id' => $bank->id, 'submission_date' => '2026-09-10']);

        return app(RecordBankResponseAction::class)->handle($this->hq, ['sales_case_id' => $case->id, 'document_submission_id' => $submission->id, 'bank_id' => $bank->id, 'response_type' => BankResponseType::Approved, 'response_date' => '2026-09-15', 'sp3k_number' => 'SP3K-X', 'sp3k_date' => '2026-09-15']);
    }

    private function createPpjb(SalesCase $case, ?string $number = null): DeveloperPpjb
    {
        return app(CreateDeveloperPpjbAction::class)->handle($this->hq, ['sales_case_id' => $case->id, 'document_date' => '2026-09-21', 'document_number' => $number]);
    }

    private function createAkad(SalesCase $case, DeveloperPpjb $ppjb, ?string $number = null): AkadRecord
    {
        return app(CreateAkadAction::class)->handle($this->hq, ['sales_case_id' => $case->id, 'developer_ppjb_id' => $ppjb->id, 'akad_date' => '2026-09-25', 'document_number' => $number]);
    }

    private function createBast(SalesCase $case, AkadRecord $akad, ?string $number = null): BastRecord
    {
        return app(CreateBastAction::class)->handle($this->hq, ['sales_case_id' => $case->id, 'akad_id' => $akad->id, 'bast_date' => '2026-09-30', 'bast_number' => $number]);
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

    private function expectUniqueConstraint(callable $callback): void
    {
        try {
            DB::transaction(function () use ($callback): void {
                $callback();
            });
            $this->fail('Expected unique constraint violation.');
        } catch (UniqueConstraintViolationException) {
            $this->assertTrue(true);
        }
    }
}
