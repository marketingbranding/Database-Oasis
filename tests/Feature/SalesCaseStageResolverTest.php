<?php

namespace Tests\Feature;

use App\Actions\CreateSalesCaseAction;
use App\BiCheckResult;
use App\DeveloperPpjbStatus;
use App\DocumentSubmissionStatus;
use App\DocumentSubmissionType;
use App\FinancingType;
use App\Models\BankProcess;
use App\Models\Branch;
use App\Models\Consumer;
use App\Models\DeveloperPpjb;
use App\Models\DocumentSubmission;
use App\Models\Project;
use App\Models\Psjb;
use App\Models\SalesCase;
use App\Models\Unit;
use App\Models\User;
use App\SalesCaseStage;
use App\Services\SalesCaseStageResolver;
use App\UserRole;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SalesCaseStageResolverTest extends TestCase
{
    use RefreshDatabase;

    private User $user;

    private Unit $unit;

    private SalesCaseStageResolver $resolver;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed();
        $this->user = User::factory()->create();
        $this->user->assignRole(UserRole::HqAdmin);
        $this->unit = Unit::factory()->for(Project::factory()->for(Branch::factory()))->create();
        $this->resolver = app(SalesCaseStageResolver::class);
    }

    public function test_create_action_normalizes_string_financing_values(): void
    {
        $case = app(CreateSalesCaseAction::class)->handle($this->user, [
            'project_id' => $this->unit->project_id,
            'unit_id' => $this->unit->id,
            'consumer_id' => Consumer::factory()->create()->id,
            'financing_type' => 'CASH',
        ]);

        $this->assertSame(FinancingType::Cash, $case->financing_type);
        $this->assertSame(SalesCaseStage::Psjb, $case->current_stage);

        $kprUnit = Unit::factory()->for($this->unit->project)->create();
        $kpr = app(CreateSalesCaseAction::class)->handle($this->user, [
            'project_id' => $kprUnit->project_id,
            'unit_id' => $kprUnit->id,
            'consumer_id' => Consumer::factory()->create()->id,
            'financing_type' => 'KPR_SUBSIDI',
        ]);

        $this->assertSame(FinancingType::KprSubsidi, $kpr->financing_type);
        $this->assertSame(SalesCaseStage::BiChecking, $kpr->current_stage);
    }

    public function test_kpr_stage_follows_persisted_evidence(): void
    {
        $case = $this->case(FinancingType::KprSubsidi);
        $this->assertSame(SalesCaseStage::BiChecking, $this->resolver->resolve($case));

        $this->bi($case, BiCheckResult::Review);
        $this->assertSame(SalesCaseStage::BiChecking, $this->resolver->resolve($case));
        $this->bi($case, BiCheckResult::Clear);
        $this->assertSame(SalesCaseStage::Psjb, $this->resolver->resolve($case));
        Psjb::factory()->create(['sales_case_id' => $case->id]);
        $this->assertSame(SalesCaseStage::Pemberkasan, $this->resolver->resolve($case));
        DocumentSubmission::factory()->create(['sales_case_id' => $case->id, 'type' => DocumentSubmissionType::Bank]);
        $this->assertSame(SalesCaseStage::ProsesBank, $this->resolver->resolve($case));
    }

    public function test_cancelled_only_kpr_submission_falls_back_to_psjb_or_bi(): void
    {
        $case = $this->case(FinancingType::KprSubsidi);
        $this->bi($case, BiCheckResult::Clear);
        $submission = DocumentSubmission::factory()->create(['sales_case_id' => $case->id, 'type' => DocumentSubmissionType::Bank, 'status' => DocumentSubmissionStatus::Cancelled]);
        $this->assertSame(SalesCaseStage::Psjb, $this->resolver->resolve($case));
        Psjb::factory()->create(['sales_case_id' => $case->id]);
        $this->assertSame(SalesCaseStage::Pemberkasan, $this->resolver->resolve($case));
        $this->assertNotNull($submission);
    }

    public function test_kpr_late_evidence_resolves_to_final_operational_steps(): void
    {
        $case = $this->case(FinancingType::KprSubsidi);
        BankProcess::factory()->approved()->create(['sales_case_id' => $case->id, 'sp3k_number' => 'SP3K-1', 'sp3k_date' => '2026-01-01']);
        $this->assertSame(SalesCaseStage::PpjbDev, $this->resolver->resolve($case));
        $ppjb = DeveloperPpjb::factory()->create(['sales_case_id' => $case->id, 'status' => DeveloperPpjbStatus::Active]);
        $this->assertSame(SalesCaseStage::Akad, $this->resolver->resolve($case));
        $case->akad()->create(['developer_ppjb_id' => $ppjb->id, 'akad_date' => '2026-01-02', 'created_by' => $this->user->id]);
        $this->assertSame(SalesCaseStage::Bast, $this->resolver->resolve($case));
        $case->bast()->create(['akad_id' => $case->akad->id, 'bast_date' => '2026-01-03', 'status' => 'COMPLETED', 'created_by' => $this->user->id]);
        $this->assertSame(SalesCaseStage::Completed, $this->resolver->resolve($case));
    }

    public function test_completed_status_without_bast_does_not_resolve_completed(): void
    {
        $case = $this->case(FinancingType::KprSubsidi, ['case_status' => 'COMPLETED']);

        $this->assertNotSame(SalesCaseStage::Completed, $this->resolver->resolve($case));
    }

    public function test_cash_stage_ignores_bi_and_uses_cash_evidence(): void
    {
        $case = $this->case(FinancingType::Cash);
        $this->assertSame(SalesCaseStage::Psjb, $this->resolver->resolve($case));
        $this->bi($case, BiCheckResult::Clear);
        $this->assertSame(SalesCaseStage::Psjb, $this->resolver->resolve($case));
        Psjb::factory()->create(['sales_case_id' => $case->id]);
        $this->assertSame(SalesCaseStage::Pemberkasan, $this->resolver->resolve($case));
        DocumentSubmission::factory()->create(['sales_case_id' => $case->id, 'type' => DocumentSubmissionType::CashInternal]);
        $this->assertSame(SalesCaseStage::PpjbDev, $this->resolver->resolve($case));
    }

    private function case(FinancingType $type, array $extra = []): SalesCase
    {
        return SalesCase::factory()->forUnit($this->unit)->create(array_merge(['financing_type' => $type], $extra));
    }

    private function bi(SalesCase $case, BiCheckResult $result): void
    {
        $case->biChecks()->create(['check_date' => now()->toDateString(), 'result' => $result, 'created_by' => $this->user->id]);
    }
}
