<?php

namespace Tests\Feature;

use App\Actions\CompleteCashPemberkasanAction;
use App\Actions\CreateAkadAction;
use App\Actions\CreateBastAction;
use App\Actions\CreateDeveloperPpjbAction;
use App\Actions\CreateDocumentSubmissionAction;
use App\Actions\CreatePsjbAction;
use App\Actions\CreateSalesCaseAction;
use App\Actions\RecordBankResponseAction;
use App\Actions\RecordBiCheckAction;
use App\BankResponseType;
use App\BiCheckResult;
use App\DocumentSubmissionType;
use App\Filament\Resources\SalesCases\Pages\ViewSalesCase;
use App\FinancingType;
use App\Models\Bank;
use App\Models\Branch;
use App\Models\Consumer;
use App\Models\DocumentSubmission;
use App\Models\Project;
use App\Models\SalesCase;
use App\Models\Unit;
use App\Models\User;
use App\SalesCaseStage;
use App\SalesCaseStatus;
use App\UserRole;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Validation\ValidationException;
use Livewire\Livewire;
use Tests\TestCase;

class PhaseSevenSixCashWorkflowTest extends TestCase
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

    public function test_cash_can_create_psjb_without_bi_and_advances_to_pemberkasan(): void
    {
        $case = $this->createCase(FinancingType::Cash);

        $psjb = app(CreatePsjbAction::class)->handle($this->hq, [
            'sales_case_id' => $case->id, 'psjb_date' => '2026-09-05',
        ]);

        $this->assertSame($case->id, $psjb->sales_case_id);
        $this->assertSame(0, $case->biChecks()->count());
        $this->assertTrue($case->refresh()->current_stage === SalesCaseStage::Pemberkasan);
    }

    public function test_kpr_cannot_create_psjb_without_clear_bi(): void
    {
        $case = $this->createCase(FinancingType::KprSubsidi);
        app(RecordBiCheckAction::class)->handle($this->hq, [
            'sales_case_id' => $case->id, 'check_date' => '2026-09-04', 'result' => BiCheckResult::Review,
        ]);

        try {
            app(CreatePsjbAction::class)->handle($this->hq, ['sales_case_id' => $case->id, 'psjb_date' => '2026-09-05']);
            $this->fail('Expected validation exception.');
        } catch (ValidationException $exception) {
            $this->assertArrayHasKey('sales_case_id', $exception->errors());
        }

        app(RecordBiCheckAction::class)->handle($this->hq, [
            'sales_case_id' => $case->id, 'check_date' => '2026-09-04', 'result' => BiCheckResult::Clear,
        ]);
        $psjb = app(CreatePsjbAction::class)->handle($this->hq, ['sales_case_id' => $case->id, 'psjb_date' => '2026-09-05']);
        $this->assertSame($case->id, $psjb->sales_case_id);
    }

    public function test_cash_cannot_create_bank_submission(): void
    {
        $case = $this->createCase(FinancingType::Cash);
        app(CreatePsjbAction::class)->handle($this->hq, ['sales_case_id' => $case->id, 'psjb_date' => '2026-09-05']);

        $this->expectException(ValidationException::class);
        app(CreateDocumentSubmissionAction::class)->handle($this->hq, [
            'sales_case_id' => $case->id, 'bank_id' => Bank::factory()->create()->id, 'submission_date' => '2026-09-06',
        ]);
    }

    public function test_cash_pemberkasan_is_internal_with_null_bank_and_no_bank_process_or_sp3k(): void
    {
        $case = $this->createCase(FinancingType::Cash);
        app(CreatePsjbAction::class)->handle($this->hq, ['sales_case_id' => $case->id, 'psjb_date' => '2026-09-05']);

        $submission = app(CompleteCashPemberkasanAction::class)->handle($this->hq, $case);

        $this->assertSame(DocumentSubmissionType::CashInternal, $submission->type);
        $this->assertNull($submission->bank_id);
        $this->assertSame($case->id, $submission->sales_case_id);
        $this->assertDatabaseCount('bank_processes', 0);
        $this->assertDatabaseMissing('bank_processes', ['sp3k_number' => $submission->id]);
        $this->assertTrue($case->refresh()->current_stage === SalesCaseStage::PpjbDev);
    }

    public function test_cash_pemberkasan_cannot_be_duplicated(): void
    {
        $case = $this->createCase(FinancingType::Cash);
        app(CreatePsjbAction::class)->handle($this->hq, ['sales_case_id' => $case->id, 'psjb_date' => '2026-09-05']);
        app(CompleteCashPemberkasanAction::class)->handle($this->hq, $case);

        $this->expectException(ValidationException::class);
        app(CompleteCashPemberkasanAction::class)->handle($this->hq, $case);
    }

    public function test_cash_cannot_create_ppjb_before_pemberkasan(): void
    {
        $case = $this->createCase(FinancingType::Cash);
        app(CreatePsjbAction::class)->handle($this->hq, ['sales_case_id' => $case->id, 'psjb_date' => '2026-09-05']);

        $this->expectException(ValidationException::class);
        app(CreateDeveloperPpjbAction::class)->handle($this->hq, [
            'sales_case_id' => $case->id, 'document_date' => '2026-09-10',
        ]);
    }

    public function test_cash_completes_full_chain_with_pemberkasan_and_bank_process_id_null(): void
    {
        $case = $this->createCase(FinancingType::Cash);
        app(CreatePsjbAction::class)->handle($this->hq, ['sales_case_id' => $case->id, 'psjb_date' => '2026-09-05']);
        app(CompleteCashPemberkasanAction::class)->handle($this->hq, $case);

        $ppjb = app(CreateDeveloperPpjbAction::class)->handle($this->hq, [
            'sales_case_id' => $case->id, 'document_date' => '2026-09-10',
        ]);
        $akad = app(CreateAkadAction::class)->handle($this->hq, [
            'sales_case_id' => $case->id, 'developer_ppjb_id' => $ppjb->id, 'akad_date' => '2026-09-20',
        ]);
        app(CreateBastAction::class)->handle($this->hq, [
            'sales_case_id' => $case->id, 'akad_id' => $akad->id, 'bast_date' => '2026-09-30',
        ]);

        $case = $case->refresh();
        $this->assertNull($ppjb->bank_process_id);
        $this->assertTrue($case->case_status === SalesCaseStatus::Completed);
        $this->assertDatabaseCount('bank_processes', 0);
        $this->assertSame(1, DocumentSubmission::query()->where('type', DocumentSubmissionType::CashInternal->value)->count());
    }

    public function test_cash_internal_submission_cannot_receive_bank_response(): void
    {
        $case = $this->createCase(FinancingType::Cash);
        app(CreatePsjbAction::class)->handle($this->hq, ['sales_case_id' => $case->id, 'psjb_date' => '2026-09-05']);
        $submission = app(CompleteCashPemberkasanAction::class)->handle($this->hq, $case);

        $this->expectException(ValidationException::class);
        app(RecordBankResponseAction::class)->handle($this->hq, [
            'sales_case_id' => $case->id, 'document_submission_id' => $submission->id,
            'bank_id' => Bank::factory()->create()->id, 'response_type' => BankResponseType::Process,
            'response_date' => '2026-09-07',
        ]);
    }

    public function test_cash_stepper_omits_bi_checking_and_proses_bank(): void
    {
        $cash = $this->createCase(FinancingType::Cash);
        $progress = $cash->stageProgress();

        $this->assertArrayNotHasKey(SalesCaseStage::BiChecking->value, $progress);
        $this->assertArrayNotHasKey(SalesCaseStage::ProsesBank->value, $progress);
        $this->assertSame(
            [
                SalesCaseStage::DataKonsumen->value,
                SalesCaseStage::Psjb->value,
                SalesCaseStage::Pemberkasan->value,
                SalesCaseStage::PpjbDev->value,
                SalesCaseStage::Akad->value,
                SalesCaseStage::Bast->value,
                SalesCaseStage::Completed->value,
            ],
            array_keys($progress),
        );
        $this->assertSame('current', $progress[SalesCaseStage::DataKonsumen->value]);
    }

    public function test_kpr_stepper_keeps_full_stage_list(): void
    {
        $kpr = $this->createCase(FinancingType::KprSubsidi);

        $this->assertSame(
            [
                SalesCaseStage::DataKonsumen->value,
                SalesCaseStage::BiChecking->value,
                SalesCaseStage::Psjb->value,
                SalesCaseStage::Pemberkasan->value,
                SalesCaseStage::ProsesBank->value,
                SalesCaseStage::PpjbDev->value,
                SalesCaseStage::Akad->value,
                SalesCaseStage::Bast->value,
                SalesCaseStage::Completed->value,
            ],
            array_keys($kpr->stageProgress()),
        );
    }

    public function test_cash_workspace_uses_cash_pemberkasan_action_without_bank_placeholders(): void
    {
        $case = $this->createCase(FinancingType::Cash);
        app(CreatePsjbAction::class)->handle($this->hq, ['sales_case_id' => $case->id, 'psjb_date' => '2026-09-05']);

        $this->actingAs($this->hq);
        Livewire::test(ViewSalesCase::class, ['record' => $case->id])
            ->assertSuccessful()
            ->assertActionVisible('completeCashPemberkasan')
            ->assertActionHidden('addPemberkasan')
            ->assertSeeText('Pemberkasan CASH')
            ->assertDontSeeText('Belum ada')
            ->assertDontSeeText('SP3K:');
    }

    public function test_completed_workspace_emphasizes_completion_information(): void
    {
        $case = $this->createCase(FinancingType::Cash);
        app(CreatePsjbAction::class)->handle($this->hq, ['sales_case_id' => $case->id, 'psjb_date' => '2026-09-05']);
        app(CompleteCashPemberkasanAction::class)->handle($this->hq, $case);
        $ppjb = app(CreateDeveloperPpjbAction::class)->handle($this->hq, ['sales_case_id' => $case->id, 'document_date' => '2026-09-10']);
        $akad = app(CreateAkadAction::class)->handle($this->hq, [
            'sales_case_id' => $case->id, 'developer_ppjb_id' => $ppjb->id, 'akad_date' => '2026-09-20',
        ]);
        app(CreateBastAction::class)->handle($this->hq, [
            'sales_case_id' => $case->id, 'akad_id' => $akad->id, 'bast_date' => '2026-09-30',
        ]);

        $this->actingAs($this->hq);
        Livewire::test(ViewSalesCase::class, ['record' => $case->id])
            ->assertSuccessful()
            ->assertSeeText('Completed')
            ->assertSeeText('Tanggal Akad')
            ->assertSeeText('Tanggal BAST')
            ->assertSeeText('20 Sep 2026')
            ->assertSeeText('30 Sep 2026');
    }

    private function createCase(FinancingType $type): SalesCase
    {
        $branch = Branch::factory()->create();
        $unit = Unit::factory()->for(Project::factory()->for($branch))->create();

        return app(CreateSalesCaseAction::class)->handle($this->hq, [
            'unit_id' => $unit->id, 'financing_type' => $type, 'consumer_id' => Consumer::factory()->create()->id,
        ]);
    }
}
