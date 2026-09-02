<?php

namespace Tests\Feature;

use App\Actions\CreateSalesCaseAction;
use App\Actions\MarkSalesCaseMundurAction;
use App\Actions\RecordBiCheckAction;
use App\BiCheckResult;
use App\Filament\Resources\BiChecks\Pages\CreateBiCheck;
use App\Filament\Resources\BiChecks\Pages\ListBiChecks;
use App\Filament\Resources\SalesCases\Pages\EditSalesCase;
use App\Filament\Resources\SalesCases\RelationManagers\BiChecksRelationManager;
use App\FinancingType;
use App\Models\BiCheck;
use App\Models\Branch;
use App\Models\Consumer;
use App\Models\Project;
use App\Models\SalesCase;
use App\Models\Unit;
use App\Models\User;
use App\SalesCaseStage;
use App\SalesCaseStatus;
use App\UserRole;
use Filament\Actions\Testing\TestAction;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\ValidationException;
use Livewire\Livewire;
use Tests\TestCase;

class PhaseThreeBiCheckTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed();
    }

    private function hqAdmin(): User
    {
        $user = User::factory()->create();
        $user->assignRole(UserRole::HqAdmin);

        return $user;
    }

    private function branchAdmin(Branch $branch): User
    {
        $user = User::factory()->for($branch)->create();
        $user->assignRole(UserRole::BranchAdmin);

        return $user;
    }

    private function makeUnit(Branch $branch): Unit
    {
        return Unit::factory()->for(Project::factory()->for($branch))->create();
    }

    private function activeCase(User $user, ?Branch $branch = null, ?Consumer $consumer = null): SalesCase
    {
        $branch ??= Branch::factory()->create();

        return app(CreateSalesCaseAction::class)->handle($user, [
            'unit_id' => $this->makeUnit($branch)->id,
            'financing_type' => FinancingType::KprSubsidi,
            'consumer_id' => ($consumer ?? Consumer::factory()->create())->id,
        ]);
    }

    private function recordBi(User $user, SalesCase $case, BiCheckResult $result, string $date = '2026-09-01'): BiCheck
    {
        return app(RecordBiCheckAction::class)->handle($user, [
            'sales_case_id' => $case->id,
            'check_date' => $date,
            'result' => $result,
        ]);
    }

    public function test_bi_review_keeps_case_active_at_bi_checking_stage(): void
    {
        $user = $this->hqAdmin();
        $case = $this->activeCase($user);

        $this->recordBi($user, $case, BiCheckResult::Review);

        $case = $case->refresh();

        $this->assertTrue($case->case_status === SalesCaseStatus::Active);
        $this->assertTrue($case->current_stage === SalesCaseStage::BiChecking);
    }

    public function test_bi_rejected_does_not_close_the_sales_case(): void
    {
        $user = $this->hqAdmin();
        $case = $this->activeCase($user);

        $this->recordBi($user, $case, BiCheckResult::Rejected, '2026-09-01');

        $case = $case->refresh();

        $this->assertTrue($case->case_status === SalesCaseStatus::Active);
        $this->assertTrue($case->current_stage === SalesCaseStage::BiChecking);
        $this->assertNull($case->closed_at);
    }

    public function test_bi_clear_advances_stage_to_psjb(): void
    {
        $user = $this->hqAdmin();
        $case = $this->activeCase($user);

        $this->recordBi($user, $case, BiCheckResult::Clear);

        $this->assertTrue($case->refresh()->current_stage === SalesCaseStage::Psjb);
    }

    public function test_multiple_bi_checks_remain_as_history_and_latest_result_wins(): void
    {
        $user = $this->hqAdmin();
        $case = $this->activeCase($user);

        $this->recordBi($user, $case, BiCheckResult::Review, '2026-09-01');
        $this->recordBi($user, $case, BiCheckResult::Clear, '2026-09-05');

        $this->assertSame(2, BiCheck::query()->whereBelongsTo($case)->count());

        $latest = BiCheck::latestForCase($case->id);

        $this->assertNotNull($latest);
        $this->assertTrue($latest->result === BiCheckResult::Clear);
    }

    public function test_bi_on_non_active_case_is_rejected(): void
    {
        $user = $this->hqAdmin();
        $case = $this->activeCase($user);

        app(MarkSalesCaseMundurAction::class)->handle($user, $case, 'Tidak lanjut');

        $this->expectException(ValidationException::class);

        $this->recordBi($user, $case->refresh(), BiCheckResult::Clear);
    }

    public function test_bi_review_at_psjb_stage_returns_case_to_bi_checking(): void
    {
        $user = $this->hqAdmin();
        $case = $this->activeCase($user);

        $this->recordBi($user, $case, BiCheckResult::Clear);
        $this->assertTrue($case->refresh()->current_stage === SalesCaseStage::Psjb);

        $this->recordBi($user, $case, BiCheckResult::Review, '2026-09-10');

        $this->assertTrue($case->refresh()->current_stage === SalesCaseStage::BiChecking);
    }

    public function test_bi_review_does_not_regress_case_beyond_psjb(): void
    {
        $user = $this->hqAdmin();
        $case = $this->activeCase($user);

        $this->recordBi($user, $case, BiCheckResult::Clear);
        $case->update(['current_stage' => SalesCaseStage::Pemberkasan]);

        $this->recordBi($user, $case, BiCheckResult::Review, '2026-09-10');

        $this->assertTrue($case->refresh()->current_stage === SalesCaseStage::Pemberkasan);
    }

    public function test_bi_records_are_isolated_across_cases_of_the_same_consumer(): void
    {
        $user = $this->hqAdmin();
        $branch = Branch::factory()->create();
        $iwan = Consumer::factory()->create(['name' => 'Iwan']);

        $caseF13 = $this->activeCase($user, $branch, $iwan);
        $this->recordBi($user, $caseF13, BiCheckResult::Clear);
        app(MarkSalesCaseMundurAction::class)->handle($user, $caseF13, 'Pindah ke kavling lain');

        $caseK03 = $this->activeCase($user, $branch, $iwan);

        $this->assertSame(1, BiCheck::query()->whereBelongsTo($caseF13)->count());
        $this->assertSame(0, BiCheck::query()->whereBelongsTo($caseK03)->count());
        $this->assertNull(BiCheck::latestForCase($caseK03->id));
    }

    public function test_bi_records_are_isolated_across_historical_consumers_of_the_same_unit(): void
    {
        $user = $this->hqAdmin();
        $branch = Branch::factory()->create();
        $unit = $this->makeUnit($branch);

        $sri = Consumer::factory()->create(['name' => 'Sri']);
        $budi = Consumer::factory()->create(['name' => 'Budi']);

        $caseSri = app(CreateSalesCaseAction::class)->handle($user, [
            'unit_id' => $unit->id,
            'financing_type' => FinancingType::KprSubsidi,
            'consumer_id' => $sri->id,
        ]);
        app(MarkSalesCaseMundurAction::class)->handle($user, $caseSri, 'Sri mundur');

        $caseBudi = app(CreateSalesCaseAction::class)->handle($user, [
            'unit_id' => $unit->id,
            'financing_type' => FinancingType::KprSubsidi,
            'consumer_id' => $budi->id,
        ]);

        $this->recordBi($user, $caseBudi, BiCheckResult::Clear);

        $this->assertSame(0, BiCheck::query()->whereBelongsTo($caseSri)->count());
        $this->assertSame(1, BiCheck::query()->whereBelongsTo($caseBudi)->count());
    }

    public function test_bi_record_cannot_be_reassigned_to_another_case(): void
    {
        $user = $this->hqAdmin();
        $case = $this->activeCase($user);
        $biCheck = $this->recordBi($user, $case, BiCheckResult::Clear);

        $this->assertFalse(Gate::forUser($user)->allows('update', $biCheck));
    }

    public function test_branch_admin_bi_list_is_scoped_to_own_branch(): void
    {
        $ownBranch = Branch::factory()->create();
        $otherBranch = Branch::factory()->create();

        $ownBi = $this->recordBi($this->hqAdmin(), $this->activeCase($this->hqAdmin(), $ownBranch), BiCheckResult::Clear);
        $otherBi = $this->recordBi($this->hqAdmin(), $this->activeCase($this->hqAdmin(), $otherBranch), BiCheckResult::Clear);

        $this->actingAs($this->branchAdmin($ownBranch));

        Livewire::test(ListBiChecks::class)
            ->assertCanSeeTableRecords([$ownBi])
            ->assertCanNotSeeTableRecords([$otherBi]);
    }

    public function test_forged_cross_branch_sales_case_id_is_blocked(): void
    {
        $otherBranch = Branch::factory()->create();
        $otherCase = $this->activeCase($this->hqAdmin(), $otherBranch);
        $ownBranchAdmin = $this->branchAdmin(Branch::factory()->create());

        $this->expectException(ValidationException::class);

        app(RecordBiCheckAction::class)->handle($ownBranchAdmin, [
            'sales_case_id' => $otherCase->id,
            'check_date' => '2026-09-01',
            'result' => BiCheckResult::Clear,
        ]);
    }

    public function test_bi_filament_create_page_records_check_and_advances_stage(): void
    {
        $user = $this->hqAdmin();
        $case = $this->activeCase($user);

        $this->actingAs($user);

        Livewire::test(CreateBiCheck::class)
            ->fillForm([
                'sales_case_id' => $case->id,
                'check_date' => '2026-09-01',
                'result' => BiCheckResult::Clear->value,
            ])
            ->call('create')
            ->assertHasNoFormErrors();

        $this->assertDatabaseHas('bi_checks', [
            'sales_case_id' => $case->id,
            'result' => BiCheckResult::Clear->value,
            'created_by' => $user->id,
        ]);
        $this->assertTrue($case->refresh()->current_stage === SalesCaseStage::Psjb);
    }

    public function test_add_bi_check_quick_action_on_sales_case_detail(): void
    {
        $user = $this->hqAdmin();
        $case = $this->activeCase($user);

        $this->actingAs($user);

        Livewire::test(BiChecksRelationManager::class, [
            'ownerRecord' => $case,
            'pageClass' => EditSalesCase::class,
        ])
            ->assertSeeText('Hasil')
            ->callAction(
                TestAction::make('recordBiCheck')->table(),
                data: [
                    'check_date' => '2026-09-01',
                    'result' => BiCheckResult::Clear->value,
                ],
            )
            ->assertHasNoFormErrors();

        $this->assertDatabaseHas('bi_checks', [
            'sales_case_id' => $case->id,
            'result' => BiCheckResult::Clear->value,
        ]);
    }

    public function test_branch_manager_is_read_only_for_bi_checks(): void
    {
        $ownBranch = Branch::factory()->create();
        $manager = User::factory()->for($ownBranch)->create();
        $manager->assignRole(UserRole::BranchManager);

        $ownBi = $this->recordBi($this->hqAdmin(), $this->activeCase($this->hqAdmin(), $ownBranch), BiCheckResult::Clear);

        $this->assertTrue(Gate::forUser($manager)->allows('viewAny', BiCheck::class));
        $this->assertTrue(Gate::forUser($manager)->allows('view', $ownBi));
        $this->assertFalse(Gate::forUser($manager)->allows('create', BiCheck::class));
    }

    public function test_auditor_is_read_only_for_all_bi_checks(): void
    {
        $auditor = User::factory()->create();
        $auditor->assignRole(UserRole::Auditor);

        $otherBranchBi = $this->recordBi($this->hqAdmin(), $this->activeCase($this->hqAdmin()), BiCheckResult::Clear);

        $this->assertTrue(Gate::forUser($auditor)->allows('view', $otherBranchBi));
        $this->assertFalse(Gate::forUser($auditor)->allows('create', BiCheck::class));
    }
}
