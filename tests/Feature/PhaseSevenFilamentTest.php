<?php

namespace Tests\Feature;

use App\Actions\CreateAkadAction;
use App\Actions\CreateDeveloperPpjbAction;
use App\Actions\CreateDocumentSubmissionAction;
use App\Actions\CreatePsjbAction;
use App\Actions\CreateSalesCaseAction;
use App\Actions\RecordBankResponseAction;
use App\Actions\RecordBiCheckAction;
use App\Actions\UpsertAkadReadinessAction;
use App\BankResponseType;
use App\BiCheckResult;
use App\DpStatus;
use App\Filament\Pages\AkadMonitoring;
use App\Filament\Pages\Monitoring;
use App\Filament\Pages\Sp3kMonitoring;
use App\Filament\Resources\AkadTargets\Pages\CreateAkadTarget;
use App\Filament\Resources\AkadTargets\Pages\ListAkadTargets;
use App\Filament\Resources\SalesCases\Pages\ViewSalesCase;
use App\FinancingType;
use App\Models\AkadTarget;
use App\Models\Bank;
use App\Models\Branch;
use App\Models\Consumer;
use App\Models\Project;
use App\Models\SalesCase;
use App\Models\Unit;
use App\Models\User;
use App\ReadinessIssueStatus;
use App\ReadinessUtilityStatus;
use App\UserRole;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class PhaseSevenFilamentTest extends TestCase
{
    use RefreshDatabase;

    private User $hq;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed();
        $this->hq = $this->user(UserRole::HqAdmin);
    }

    public function test_monitoring_overview_renders_kpi_cards_and_authorized_options(): void
    {
        $branch = Branch::factory()->create();
        AkadTarget::factory()->for($branch)->create(['period_month' => now()->startOfMonth(), 'target' => 5]);

        $this->actingAs($this->hq);
        $component = Livewire::test(Monitoring::class)
            ->assertSuccessful()
            ->assertSeeText('Target Akad')
            ->assertSeeText((string) $branch->name)
            ->assertSeeText('Semua Cabang');

        $this->assertTrue($component->instance()->canAccess());
    }

    public function test_monitoring_branch_admin_defaults_to_own_branch_and_cannot_forge_other_branch_options(): void
    {
        $ownBranch = Branch::factory()->create();
        $otherBranch = Branch::factory()->create();
        $branchAdmin = $this->user(UserRole::BranchAdmin, $ownBranch);

        $this->actingAs($branchAdmin);
        Livewire::test(Monitoring::class)
            ->assertSuccessful()
            ->assertSeeText($ownBranch->name)
            ->assertDontSeeText($otherBranch->name);
    }

    public function test_monitoring_page_denies_roleless_or_read_only_panel_users(): void
    {
        $activeUser = User::factory()->create();
        $this->actingAs($activeUser);

        $this->assertFalse(Monitoring::canAccess());
        $this->assertFalse(Sp3kMonitoring::canAccess());
        $this->assertFalse(AkadMonitoring::canAccess());
    }

    public function test_sp3k_monitoring_renders_stock_rows_and_server_scope_blocks_forged_filter(): void
    {
        $ownBranch = Branch::factory()->create();
        $otherBranch = Branch::factory()->create();
        $case = $this->approvedCase($ownBranch);
        $this->approvedCase($otherBranch);
        $branchAdmin = $this->user(UserRole::BranchAdmin, $ownBranch);

        $this->actingAs($branchAdmin);
        Livewire::test(Sp3kMonitoring::class, ['branchId' => $otherBranch->id])
            ->assertSuccessful()
            ->assertSeeText($case->consumer->name)
            ->assertSeeText($ownBranch->name)
            ->assertDontSeeText($otherBranch->name);
    }

    public function test_sp3k_issue_filter_matches_central_issue_definition(): void
    {
        $ownBranch = Branch::factory()->create();
        $case = $this->approvedCase($ownBranch);
        app(UpsertAkadReadinessAction::class)->handle($this->hq, $case, [
            'building_status' => ReadinessIssueStatus::Clear,
            'dp_status' => DpStatus::Complete,
            'electricity_status' => ReadinessUtilityStatus::NotInstalled,
            'water_status' => ReadinessUtilityStatus::Installed,
            'consumer_status' => ReadinessIssueStatus::Clear,
        ]);
        $branchAdmin = $this->user(UserRole::BranchAdmin, $ownBranch);

        $this->actingAs($branchAdmin);
        Livewire::test(Sp3kMonitoring::class, ['issue' => 'UTILITAS'])
            ->assertSuccessful()
            ->assertSeeText($case->consumer->name);
    }

    public function test_akad_monitoring_renders_selected_month_and_week_bucket(): void
    {
        $case = $this->approvedCase();
        $this->akad($case, '2026-09-09');

        $this->actingAs($this->hq);
        Livewire::test(AkadMonitoring::class, ['month' => '2026-09'])
            ->assertSuccessful()
            ->assertSeeText($case->consumer->name)
            ->assertSeeText('M2');
    }

    public function test_target_resource_scopes_rows_and_blocks_branch_admin_create(): void
    {
        $ownBranch = Branch::factory()->create();
        $otherBranch = Branch::factory()->create();
        AkadTarget::factory()->for($ownBranch)->create(['period_month' => '2026-09-01']);
        AkadTarget::factory()->for($otherBranch)->create(['period_month' => '2026-09-01']);
        $branchAdmin = $this->user(UserRole::BranchAdmin, $ownBranch);

        $this->actingAs($branchAdmin);
        Livewire::test(ListAkadTargets::class)->assertSuccessful();

        Livewire::test(CreateAkadTarget::class)->assertForbidden();
    }

    public function test_target_resource_hq_can_create_project_target_with_branch_validation(): void
    {
        $branch = Branch::factory()->create();
        $project = Project::factory()->for($branch)->create();
        $otherBranch = Branch::factory()->create();

        $this->actingAs($this->hq);

        Livewire::test(CreateAkadTarget::class)
            ->fillForm(['branch_id' => $branch->id, 'project_id' => $project->id, 'period_month' => '2026-09-15', 'target' => 8])
            ->call('create')
            ->assertHasNoFormErrors();

        $this->assertDatabaseHas('akad_targets', [
            'branch_id' => $branch->id, 'project_id' => $project->id, 'target' => 8,
        ]);

        Livewire::test(CreateAkadTarget::class)
            ->fillForm(['branch_id' => $branch->id, 'project_id' => Project::factory()->for($otherBranch)->create()->id, 'period_month' => '2026-10-01', 'target' => 2])
            ->call('create')
            ->assertHasFormErrors(['project_id']);

        $this->assertDatabaseCount('akad_targets', 1);
    }

    public function test_workspace_readiness_action_creates_and_updates_snapshot(): void
    {
        $case = $this->approvedCase();

        $this->actingAs($this->hq);

        Livewire::test(ViewSalesCase::class, ['record' => $case->id])
            ->assertActionVisible('updateReadiness')
            ->callAction('updateReadiness', [
                'building_progress' => 70,
                'building_status' => ReadinessIssueStatus::Issue,
                'dp_status' => DpStatus::Incomplete,
                'electricity_status' => ReadinessUtilityStatus::NotInstalled,
                'water_status' => ReadinessUtilityStatus::Installed,
                'consumer_status' => ReadinessIssueStatus::Clear,
                'consumer_note' => 'Di luar kota',
            ])
            ->assertHasNoActionErrors();

        $readiness = $case->akadReadiness()->sole();
        $this->assertSame(3, $readiness->issueCount());
        $this->assertSame(70, $readiness->building_progress);

        Livewire::test(ViewSalesCase::class, ['record' => $case->id])
            ->callAction('updateReadiness', [
                'building_progress' => 85,
                'building_status' => ReadinessIssueStatus::Clear,
                'dp_status' => DpStatus::Complete,
                'electricity_status' => ReadinessUtilityStatus::Installed,
                'water_status' => ReadinessUtilityStatus::Installed,
                'consumer_status' => ReadinessIssueStatus::Clear,
            ])
            ->assertHasNoActionErrors();

        $this->assertSame(0, $readiness->refresh()->issueCount());
        $this->assertSame(85, $readiness->building_progress);
        $this->assertSame(1, $case->akadReadiness()->count());
    }

    public function test_post_akad_workspace_hides_readiness_action(): void
    {
        $case = $this->approvedCase();
        $this->akad($case, '2026-09-25');

        $this->actingAs($this->hq);

        Livewire::test(ViewSalesCase::class, ['record' => $case->id])
            ->assertActionHidden('updateReadiness');
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

    private function approvedCase(?Branch $branch = null): SalesCase
    {
        $branch ??= Branch::factory()->create();
        $project = Project::factory()->for($branch)->create();
        $unit = Unit::factory()->for($project)->create();
        $case = app(CreateSalesCaseAction::class)->handle($this->hq, [
            'unit_id' => $unit->id, 'consumer_id' => Consumer::factory()->create()->id,
            'financing_type' => FinancingType::KprSubsidi,
        ]);

        app(RecordBiCheckAction::class)->handle($this->hq, [
            'sales_case_id' => $case->id, 'check_date' => '2026-09-01', 'result' => BiCheckResult::Clear,
        ]);
        app(CreatePsjbAction::class)->handle($this->hq, ['sales_case_id' => $case->id, 'psjb_date' => '2026-09-02']);
        $submission = app(CreateDocumentSubmissionAction::class)->handle($this->hq, [
            'sales_case_id' => $case->id, 'bank_id' => Bank::factory()->create()->id, 'submission_date' => '2026-09-05',
        ]);
        app(RecordBankResponseAction::class)->handle($this->hq, [
            'sales_case_id' => $case->id, 'document_submission_id' => $submission->id, 'bank_id' => $submission->bank_id,
            'response_type' => BankResponseType::Approved, 'response_date' => '2026-09-10',
            'sp3k_number' => 'SP3K-UI', 'sp3k_date' => '2026-09-10',
        ]);

        return $case->refresh();
    }

    private function akad(SalesCase $case, string $date): void
    {
        app(CreateAkadAction::class)->handle($this->hq, [
            'sales_case_id' => $case->id,
            'developer_ppjb_id' => app(CreateDeveloperPpjbAction::class)->handle($this->hq, [
                'sales_case_id' => $case->id, 'document_date' => $date,
            ])->id,
            'akad_date' => $date,
        ]);
    }
}
