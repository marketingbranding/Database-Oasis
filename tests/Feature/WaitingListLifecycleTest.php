<?php

namespace Tests\Feature;

use App\Actions\CompleteCashPemberkasanAction;
use App\Actions\CreateDeveloperPpjbAction;
use App\Actions\CreateDocumentSubmissionAction;
use App\Actions\CreatePsjbAction;
use App\Actions\CreateSalesCaseAction;
use App\Actions\MoveSalesCaseUnitAction;
use App\Actions\RecordBankResponseAction;
use App\Actions\RecordBiCheckAction;
use App\Actions\ReissueDeveloperPpjbAction;
use App\BankResponseType;
use App\BiCheckResult;
use App\DeveloperPpjbStatus;
use App\DocumentSubmissionType;
use App\FinancingType;
use App\Models\Bank;
use App\Models\Branch;
use App\Models\Consumer;
use App\Models\DeveloperPpjb;
use App\Models\Project;
use App\Models\SalesCase;
use App\Models\Unit;
use App\Models\User;
use App\SalesCaseStatus;
use App\UnitStatus;
use App\UserRole;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class WaitingListLifecycleTest extends TestCase
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

    public function test_kpr_waiting_list_keeps_evidence_when_unit_is_assigned_then_creates_ppjb(): void
    {
        $project = Project::factory()->for(Branch::factory())->create();
        $case = $this->waitingCase($project, FinancingType::KprSubsidi);
        $bank = Bank::factory()->create();
        app(RecordBiCheckAction::class)->handle($this->hq, ['sales_case_id' => $case->id, 'check_date' => '2026-09-01', 'result' => BiCheckResult::Clear]);
        $psjb = app(CreatePsjbAction::class)->handle($this->hq, ['sales_case_id' => $case->id, 'psjb_date' => '2026-09-02']);
        $submission = app(CreateDocumentSubmissionAction::class)->handle($this->hq, ['sales_case_id' => $case->id, 'bank_id' => $bank->id, 'submission_date' => '2026-09-03']);
        $approval = app(RecordBankResponseAction::class)->handle($this->hq, ['sales_case_id' => $case->id, 'document_submission_id' => $submission->id, 'bank_id' => $bank->id, 'response_type' => BankResponseType::Approved, 'response_date' => '2026-09-04', 'sp3k_number' => 'SP3K-WL', 'sp3k_date' => '2026-09-04']);

        $this->assertNull($case->refresh()->unit_id);
        $this->assertSame(SalesCaseStatus::Active, $case->case_status);
        $this->assertNull($approval->sp3k_number);
        $this->assertNotNull($approval->sp3k_code);
        $this->assertTrue($approval->is_authoritative);
        $this->assertWaitingListPpjbRejected($case);

        $unit = Unit::factory()->for($project)->create();
        $assigned = app(MoveSalesCaseUnitAction::class)->handle($this->hq, $case, $unit->id, 'Kavling tersedia');
        $ppjb = app(CreateDeveloperPpjbAction::class)->handle($this->hq, ['sales_case_id' => $case->id, 'document_date' => '2026-09-05']);

        $this->assertSame($case->id, $assigned->id);
        $this->assertSame($unit->id, $assigned->unit_id);
        $this->assertSame(UnitStatus::Booking, $unit->fresh()->status);
        $this->assertSame(1, $case->biChecks()->count());
        $this->assertSame($psjb->id, $case->activePsjb->id);
        $this->assertSame($submission->id, $case->documentSubmissions()->firstOrFail()->id);
        $this->assertSame($approval->id, $case->currentApprovedBankProcess->id);
        $this->assertSame($case->id, $ppjb->sales_case_id);
    }

    public function test_cash_waiting_list_assigns_unit_then_creates_ppjb_without_bank(): void
    {
        $project = Project::factory()->for(Branch::factory())->create();
        $case = $this->waitingCase($project, FinancingType::Cash);
        app(CreatePsjbAction::class)->handle($this->hq, ['sales_case_id' => $case->id, 'psjb_date' => '2026-09-02']);
        $submission = app(CompleteCashPemberkasanAction::class)->handle($this->hq, $case);

        $this->assertNull($case->refresh()->unit_id);
        $this->assertSame(DocumentSubmissionType::CashInternal, $submission->type);
        $this->assertWaitingListPpjbRejected($case);

        $unit = Unit::factory()->for($project)->create();
        $assigned = app(MoveSalesCaseUnitAction::class)->handle($this->hq, $case, $unit->id, 'Kavling tersedia');
        $ppjb = app(CreateDeveloperPpjbAction::class)->handle($this->hq, ['sales_case_id' => $case->id, 'document_date' => '2026-09-05']);

        $this->assertSame($case->id, $assigned->id);
        $this->assertSame(UnitStatus::Booking, $unit->fresh()->status);
        $this->assertSame($case->id, $ppjb->sales_case_id);
        $this->assertNull($ppjb->bank_process_id);
    }

    public function test_branch_admin_cannot_reissue_other_branch_ppjb(): void
    {
        $branchA = Branch::factory()->create();
        $branchB = Branch::factory()->create();
        $admin = User::factory()->create(['branch_id' => $branchA->id]);
        $admin->assignRole(UserRole::BranchAdmin);
        $unit = Unit::factory()->for(Project::factory()->for($branchB))->create();
        $case = SalesCase::factory()->forUnit($unit)->create();
        $old = DeveloperPpjb::factory()->create(['sales_case_id' => $case->id, 'status' => DeveloperPpjbStatus::Active]);

        try {
            app(ReissueDeveloperPpjbAction::class)->handle($admin, $case, ['document_date' => '2026-09-05']);
            $this->fail('Cross-branch reissue unexpectedly succeeded.');
        } catch (ValidationException $exception) {
            $this->assertStringContainsString('luar cabang', $exception->getMessage());
        }

        $this->assertSame(DeveloperPpjbStatus::Active, $old->refresh()->status);
        $this->assertSame(1, $case->developerPpjbs()->count());
    }

    private function waitingCase(Project $project, FinancingType $type): SalesCase
    {
        return app(CreateSalesCaseAction::class)->handle($this->hq, ['project_id' => $project->id, 'unit_id' => null, 'consumer_id' => Consumer::factory()->create()->id, 'financing_type' => $type]);
    }

    private function assertWaitingListPpjbRejected(SalesCase $case): void
    {
        try {
            app(CreateDeveloperPpjbAction::class)->handle($this->hq, ['sales_case_id' => $case->id, 'document_date' => '2026-09-05']);
            $this->fail('Waiting List PPJB unexpectedly succeeded.');
        } catch (ValidationException $exception) {
            $this->assertStringContainsString('kavling', strtolower($exception->getMessage()));
        }
    }
}
