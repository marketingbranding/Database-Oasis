<?php

namespace Tests\Feature;

use App\Actions\UpsertAkadReadinessAction;
use App\BankResponseType;
use App\BastStatus;
use App\DeveloperPpjbStatus;
use App\DpStatus;
use App\Filament\Pages\AkadMonitoring;
use App\Filament\Pages\Monitoring;
use App\Filament\Pages\Sp3kMonitoring;
use App\FinancingType;
use App\KendalaCategory;
use App\Models\AkadReadiness;
use App\Models\AkadRecord;
use App\Models\AkadTarget;
use App\Models\Bank;
use App\Models\BankProcess;
use App\Models\BastRecord;
use App\Models\Branch;
use App\Models\Consumer;
use App\Models\DeveloperPpjb;
use App\Models\Project;
use App\Models\SalesCase;
use App\Models\Unit;
use App\Models\User;
use App\ReadinessIssueStatus;
use App\ReadinessUtilityStatus;
use App\SalesCaseStage;
use App\SalesCaseStatus;
use App\Services\Monitoring\MonitoringPeriod;
use App\Services\Monitoring\MonitoringScope;
use App\Services\Monitoring\MonitoringService;
use App\Sp3kAgingBucket;
use App\UserRole;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class PhaseSevenMonitoringTest extends TestCase
{
    use RefreshDatabase;

    private User $hq;

    private MonitoringService $monitoring;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed();
        Carbon::setTestNow('2026-10-31 12:00:00');
        $this->hq = $this->user(UserRole::HqAdmin);
        $this->monitoring = app(MonitoringService::class);
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();
        parent::tearDown();
    }

    public function test_akad_m1_to_m4_sum_exactly_matches_monthly_realization(): void
    {
        $branch = Branch::factory()->create();
        foreach (['2026-09-02', '2026-09-06', '2026-09-09', '2026-09-13', '2026-09-17', '2026-09-22', '2026-09-30'] as $date) {
            $this->akad($this->salesCase($branch), $date);
        }
        $this->akad($this->salesCase($branch), '2026-10-01');
        $period = new MonitoringPeriod('2026-09');
        $scope = new MonitoringScope($this->hq, $branch->id);

        $weekly = $this->monitoring->akadWeeklyBreakdown($period, $scope);

        $this->assertSame(['M1' => 2, 'M2' => 2, 'M3' => 1, 'M4' => 2], $weekly);
        $this->assertSame(7, array_sum($weekly));
        $this->assertSame($this->monitoring->akadRealization($period, $scope), array_sum($weekly));
    }

    public function test_sp3k_stock_counts_only_active_kpr_authoritative_valid_approval_without_akad(): void
    {
        $branch = Branch::factory()->create();
        $stock = $this->salesCase($branch);
        $this->approve($stock, 'A');
        $withAkad = $this->salesCase($branch);
        $this->approve($withAkad, 'B');
        $this->akad($withAkad, '2026-09-20');
        $cash = $this->salesCase($branch, FinancingType::Cash);
        $rejected = $this->salesCase($branch);
        $this->bankProcess($rejected, BankResponseType::Rejected);

        $ids = $this->monitoring->sp3kStockQuery(new MonitoringScope($this->hq, $branch->id))->pluck('id')->all();

        $this->assertSame([$stock->id], $ids);
        $this->assertNotContains($withAkad->id, $ids);
        $this->assertNotContains($cash->id, $ids);
        $this->assertNotContains($rejected->id, $ids);
    }

    public function test_multiple_bank_history_uses_only_authoritative_approved_bank(): void
    {
        $case = $this->salesCase();
        $btn = Bank::factory()->create(['name' => 'BTN']);
        $bri = Bank::factory()->create(['name' => 'BRI']);
        $this->bankProcess($case, BankResponseType::Rejected, $btn);
        $approval = $this->approve($case, 'BRI-SP3K', $bri);

        $stock = $this->monitoring->sp3kStockQuery(new MonitoringScope($this->hq))
            ->with('currentApprovedBankProcess.bank')
            ->sole();

        $this->assertSame($case->id, $stock->id);
        $this->assertSame($approval->id, $stock->currentApprovedBankProcess->id);
        $this->assertSame('BRI', $stock->currentApprovedBankProcess->bank->name);
    }

    public function test_duplicate_sp3k_numbers_count_each_sales_case_once(): void
    {
        $caseA = $this->salesCase();
        $caseB = $this->salesCase();
        $this->approve($caseA, '123');
        $this->approve($caseB, '123');

        $this->assertSame(2, $this->monitoring->sp3kStockQuery(new MonitoringScope($this->hq))->count());
    }

    public function test_explicit_kendala_counts_units_and_categories_separately(): void
    {
        $case = $this->salesCase();
        $this->approve($case);
        AkadReadiness::factory()->for($case)->create([
            'building_status' => ReadinessIssueStatus::Issue,
            'dp_status' => DpStatus::Incomplete,
            'electricity_status' => ReadinessUtilityStatus::NotInstalled,
            'water_status' => ReadinessUtilityStatus::Installed,
            'consumer_status' => ReadinessIssueStatus::Clear,
        ]);
        $scope = new MonitoringScope($this->hq);

        $breakdown = $this->monitoring->issueBreakdown($scope);

        $this->assertSame(1, $this->monitoring->sp3kWithIssuesQuery($scope)->count());
        $this->assertSame(3, array_sum($breakdown));
        $this->assertSame([
            KendalaCategory::Bangunan->value => 1,
            KendalaCategory::DpKonsumen->value => 1,
            KendalaCategory::Utilitas->value => 1,
            KendalaCategory::Konsumen->value => 0,
        ], $breakdown);
    }

    public function test_unknown_readiness_is_incomplete_but_not_kendala(): void
    {
        $case = $this->salesCase();
        $this->approve($case);
        AkadReadiness::factory()->for($case)->create();
        $scope = new MonitoringScope($this->hq);

        $this->assertSame(0, $this->monitoring->sp3kWithIssuesQuery($scope)->count());
        $this->assertSame(0, array_sum($this->monitoring->issueBreakdown($scope)));
        $this->assertSame(1, $this->monitoring->readinessIncompleteQuery($scope)->count());
    }

    public function test_twenty_six_sp3k_units_with_two_categories_produce_fifty_two_open_kendala(): void
    {
        for ($index = 0; $index < 26; $index++) {
            $case = $this->salesCase();
            $this->approve($case);
            AkadReadiness::factory()->for($case)->create([
                'building_status' => ReadinessIssueStatus::Issue,
                'dp_status' => DpStatus::Complete,
                'electricity_status' => ReadinessUtilityStatus::NotInstalled,
                'water_status' => ReadinessUtilityStatus::Installed,
                'consumer_status' => ReadinessIssueStatus::Clear,
            ]);
        }
        $scope = new MonitoringScope($this->hq);

        $this->assertSame(26, $this->monitoring->sp3kWithIssuesQuery($scope)->count());
        $this->assertSame(52, array_sum($this->monitoring->issueBreakdown($scope)));
    }

    public function test_akad_removes_case_from_sp3k_metrics_but_counts_akad_realization(): void
    {
        $case = $this->salesCase();
        $this->approve($case, sp3kDate: '2026-09-01');
        AkadReadiness::factory()->for($case)->create(['building_status' => ReadinessIssueStatus::Issue]);
        $scope = new MonitoringScope($this->hq);
        $this->assertSame(1, $this->monitoring->sp3kStockQuery($scope)->count());

        $this->akad($case, '2026-09-25');

        $this->assertSame(0, $this->monitoring->sp3kStockQuery($scope)->count());
        $this->assertSame(0, $this->monitoring->sp3kWithIssuesQuery($scope)->count());
        $this->assertSame(0, array_sum($this->monitoring->sp3kAging($scope)));
        $this->assertSame(1, $this->monitoring->akadRealization(new MonitoringPeriod('2026-09'), $scope));
    }

    public function test_sp3k_aging_uses_authoritative_sp3k_date_and_frozen_current_date(): void
    {
        foreach (['2026-10-31', '2026-10-20', '2026-10-10', '2026-09-01'] as $date) {
            $this->approve($this->salesCase(), sp3kDate: $date);
        }

        $this->assertSame([
            Sp3kAgingBucket::ZeroToSeven->value => 1,
            Sp3kAgingBucket::EightToFourteen->value => 1,
            Sp3kAgingBucket::FifteenToThirty->value => 1,
            Sp3kAgingBucket::OverThirty->value => 1,
        ], $this->monitoring->sp3kAging(new MonitoringScope($this->hq)));
    }

    public function test_branch_and_project_targets_are_independent_and_missing_target_is_neutral(): void
    {
        $branch = Branch::factory()->create();
        $project = Project::factory()->for($branch)->create();
        AkadTarget::factory()->for($branch)->create(['period_month' => '2026-09-17', 'target' => 20]);
        AkadTarget::factory()->for($branch)->for($project)->create(['period_month' => '2026-09-01', 'target' => 7]);
        $period = new MonitoringPeriod('2026-09');

        $this->assertSame(20, $this->monitoring->akadTarget($period, new MonitoringScope($this->hq, $branch->id)));
        $this->assertSame(7, $this->monitoring->akadTarget($period, new MonitoringScope($this->hq, $branch->id, $project->id)));
        $this->assertNull($this->monitoring->akadTarget(new MonitoringPeriod('2026-10'), new MonitoringScope($this->hq, $branch->id)));
        $this->assertSame('2026-09-01', AkadTarget::query()->whereNull('project_id')->sole()->period_month->toDateString());
    }

    public function test_duplicate_branch_target_for_month_is_structurally_blocked(): void
    {
        $branch = Branch::factory()->create();
        AkadTarget::factory()->for($branch)->create(['period_month' => '2026-09-01']);

        $this->expectException(UniqueConstraintViolationException::class);
        AkadTarget::factory()->for($branch)->create(['period_month' => '2026-09-30']);
    }

    public function test_duplicate_project_target_for_month_is_structurally_blocked(): void
    {
        $branch = Branch::factory()->create();
        $project = Project::factory()->for($branch)->create();
        AkadTarget::factory()->for($branch)->for($project)->create(['period_month' => '2026-09-01']);

        $this->expectException(UniqueConstraintViolationException::class);
        AkadTarget::factory()->for($branch)->for($project)->create(['period_month' => '2026-09-30']);
    }

    public function test_target_permissions_allow_hq_mutation_and_make_other_monitoring_roles_read_only(): void
    {
        $target = AkadTarget::factory()->create();
        $branchAdmin = $this->user(UserRole::BranchAdmin, $target->branch);
        $branchManager = $this->user(UserRole::BranchManager, $target->branch);
        $management = $this->user(UserRole::Management);
        $auditor = $this->user(UserRole::Auditor);

        $this->assertTrue($this->hq->can('create', AkadTarget::class));
        $this->assertTrue($this->hq->can('update', $target));
        foreach ([$branchAdmin, $branchManager, $management, $auditor] as $user) {
            $this->assertTrue($user->can('view', $target), $user->roles->firstOrFail()->name);
            $this->assertFalse($user->can('create', AkadTarget::class));
            $this->assertFalse($user->can('update', $target));
        }
    }

    public function test_monitoring_access_and_forged_branch_or_project_scope_are_server_rejected(): void
    {
        $branchA = Branch::factory()->create();
        $branchB = Branch::factory()->create();
        $projectB = Project::factory()->for($branchB)->create();
        $branchAdmin = $this->user(UserRole::BranchAdmin, $branchA);
        $branchManager = $this->user(UserRole::BranchManager, $branchA);
        $management = $this->user(UserRole::Management);
        $auditor = $this->user(UserRole::Auditor);

        foreach ([$this->hq, $branchAdmin, $branchManager, $management, $auditor] as $user) {
            $this->actingAs($user);
            $this->assertTrue(Monitoring::canAccess());
            $this->assertTrue(Sp3kMonitoring::canAccess());
            $this->assertTrue(AkadMonitoring::canAccess());
        }

        $this->expectException(ValidationException::class);
        new MonitoringScope($branchAdmin, $branchB->id, $projectB->id);
    }

    public function test_branch_scope_cannot_use_project_from_another_branch(): void
    {
        $branchA = Branch::factory()->create();
        $branchB = Branch::factory()->create();
        $projectB = Project::factory()->for($branchB)->create();
        $branchAdmin = $this->user(UserRole::BranchAdmin, $branchA);

        $this->expectException(ValidationException::class);
        new MonitoringScope($branchAdmin, $branchA->id, $projectB->id);
    }

    public function test_readiness_permissions_enforce_branch_and_post_akad_read_only(): void
    {
        $branchA = Branch::factory()->create();
        $case = $this->salesCase($branchA);
        $branchAdmin = $this->user(UserRole::BranchAdmin, $branchA);
        $otherAdmin = $this->user(UserRole::BranchAdmin, Branch::factory()->create());
        $payload = $this->clearReadiness();

        $readiness = app(UpsertAkadReadinessAction::class)->handle($branchAdmin, $case, $payload);
        $this->assertSame($branchAdmin->id, $readiness->updated_by);
        $this->assertTrue($readiness->isComplete());
        $this->assertSame(1, $case->akadReadiness()->count());

        try {
            app(UpsertAkadReadinessAction::class)->handle($otherAdmin, $case, $payload);
            $this->fail('Expected authorization failure.');
        } catch (AuthorizationException) {
            $this->assertSame($branchAdmin->id, $readiness->refresh()->updated_by);
        }

        $this->akad($case, '2026-09-25');
        $this->expectException(ValidationException::class);
        app(UpsertAkadReadinessAction::class)->handle($this->hq, $case, $payload);
    }

    public function test_readiness_validation_rejects_progress_outside_zero_to_one_hundred(): void
    {
        $case = $this->salesCase();

        $this->expectException(ValidationException::class);
        app(UpsertAkadReadinessAction::class)->handle($this->hq, $case, [...$this->clearReadiness(), 'building_progress' => 101]);
    }

    public function test_bast_monthly_counts_only_completed_bast_in_selected_period(): void
    {
        $branch = Branch::factory()->create();
        $akadA = $this->akad($this->salesCase($branch), '2026-09-10');
        $akadB = $this->akad($this->salesCase($branch), '2026-09-11');
        $akadC = $this->akad($this->salesCase($branch), '2026-09-12');
        $this->bast($akadA, '2026-09-20', BastStatus::Completed);
        $this->bast($akadB, '2026-09-21', BastStatus::Cancelled);
        $this->bast($akadC, '2026-10-01', BastStatus::Completed);

        $this->assertSame(1, $this->monitoring->bastRealization(new MonitoringPeriod('2026-09'), new MonitoringScope($this->hq, $branch->id)));
    }

    public function test_branch_admin_overview_kpis_exclude_other_branches(): void
    {
        $ownBranch = Branch::factory()->create();
        $otherBranch = Branch::factory()->create();
        foreach ([$ownBranch, $otherBranch] as $branch) {
            $case = $this->salesCase($branch);
            $this->approve($case, sp3kDate: '2026-10-20');
            AkadReadiness::factory()->for($case)->create(['building_status' => ReadinessIssueStatus::Issue]);
            $this->akad($this->salesCase($branch), '2026-10-15');
        }
        $branchAdmin = $this->user(UserRole::BranchAdmin, $ownBranch);
        $overview = $this->monitoring->overview(new MonitoringPeriod('2026-10'), new MonitoringScope($branchAdmin));

        $this->assertSame(1, $overview['sp3k_stock']);
        $this->assertSame(1, $overview['sp3k_with_issues']);
        $this->assertSame(1, $overview['akad']);
        $this->assertSame(1, $overview['weekly']['M3']);
        $this->assertSame(0, $overview['bast']);
    }

    private function user(UserRole $role, ?Branch $branch = null): User
    {
        $factory = User::factory();
        if ($branch !== null) {
            $factory = $factory->for($branch);
        }
        $user = $factory->create();
        $user->assignRole($role);

        return $user;
    }

    private function salesCase(?Branch $branch = null, FinancingType $financing = FinancingType::KprSubsidi): SalesCase
    {
        $branch ??= Branch::factory()->create();
        $project = Project::factory()->for($branch)->create();
        $unit = Unit::factory()->for($project)->create();

        return SalesCase::create([
            'consumer_id' => Consumer::factory()->create()->id,
            'unit_id' => $unit->id,
            'project_id' => $project->id,
            'branch_id' => $branch->id,
            'financing_type' => $financing,
            'current_stage' => SalesCaseStage::PpjbDev,
            'case_status' => SalesCaseStatus::Active,
            'created_by' => $this->hq->id,
        ]);
    }

    private function bankProcess(SalesCase $case, BankResponseType $response, ?Bank $bank = null, ?string $sp3kNumber = null, ?string $sp3kDate = null): BankProcess
    {
        return BankProcess::create([
            'sales_case_id' => $case->id,
            'bank_id' => ($bank ?? Bank::factory()->create())->id,
            'response_type' => $response,
            'response_date' => $sp3kDate ?? '2026-09-01',
            'sp3k_number' => $sp3kNumber,
            'sp3k_date' => $sp3kDate,
            'is_authoritative' => $response === BankResponseType::Approved,
            'created_by' => $this->hq->id,
        ]);
    }

    private function approve(SalesCase $case, string $number = 'SP3K', ?Bank $bank = null, string $sp3kDate = '2026-10-01'): BankProcess
    {
        return $this->bankProcess($case, BankResponseType::Approved, $bank, $number, $sp3kDate);
    }

    private function akad(SalesCase $case, string $date): AkadRecord
    {
        $ppjb = DeveloperPpjb::create([
            'sales_case_id' => $case->id,
            'document_date' => $date,
            'status' => DeveloperPpjbStatus::Active,
            'created_by' => $this->hq->id,
        ]);

        return AkadRecord::create([
            'sales_case_id' => $case->id,
            'developer_ppjb_id' => $ppjb->id,
            'akad_date' => $date,
            'created_by' => $this->hq->id,
        ]);
    }

    private function bast(AkadRecord $akad, string $date, BastStatus $status): BastRecord
    {
        return BastRecord::create([
            'sales_case_id' => $akad->sales_case_id,
            'akad_id' => $akad->id,
            'bast_date' => $date,
            'status' => $status,
            'created_by' => $this->hq->id,
        ]);
    }

    /** @return array<string, mixed> */
    private function clearReadiness(): array
    {
        return [
            'building_progress' => 80,
            'building_status' => ReadinessIssueStatus::Clear,
            'dp_status' => DpStatus::Complete,
            'electricity_status' => ReadinessUtilityStatus::Installed,
            'water_status' => ReadinessUtilityStatus::Installed,
            'consumer_status' => ReadinessIssueStatus::Clear,
        ];
    }
}
