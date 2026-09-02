<?php

namespace Tests\Feature;

use App\Actions\CancelPsjbAction;
use App\Actions\CreatePsjbAction;
use App\Actions\CreateSalesCaseAction;
use App\Actions\MarkSalesCaseMundurAction;
use App\Actions\RecordBiCheckAction;
use App\Actions\ReissuePsjbAction;
use App\BiCheckResult;
use App\Filament\Resources\Psjbs\Pages\CreatePsjb;
use App\Filament\Resources\Psjbs\Pages\ListPsjbs;
use App\FinancingType;
use App\Models\BiCheck;
use App\Models\Branch;
use App\Models\Consumer;
use App\Models\Project;
use App\Models\Psjb;
use App\Models\SalesCase;
use App\Models\Unit;
use App\Models\User;
use App\PsjbStatus;
use App\SalesCaseStage;
use App\SalesCaseStatus;
use App\UserRole;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\ValidationException;
use Livewire\Livewire;
use Tests\TestCase;

class PhaseThreePsjbTest extends TestCase
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

    private function activeCase(User $user, ?Branch $branch = null, ?Consumer $consumer = null, ?User $coordinator = null): SalesCase
    {
        $branch ??= Branch::factory()->create();

        return app(CreateSalesCaseAction::class)->handle($user, [
            'unit_id' => $this->makeUnit($branch)->id,
            'financing_type' => FinancingType::KprSubsidi,
            'consumer_id' => ($consumer ?? Consumer::factory()->create())->id,
            'coordinator_id' => $coordinator?->id,
        ]);
    }

    private function clearBi(User $user, SalesCase $case, string $date = '2026-09-01'): BiCheck
    {
        return app(RecordBiCheckAction::class)->handle($user, [
            'sales_case_id' => $case->id,
            'check_date' => $date,
            'result' => BiCheckResult::Clear,
        ]);
    }

    public function test_psjb_requires_latest_clear_bi(): void
    {
        $user = $this->hqAdmin();
        $caseWithoutBi = $this->activeCase($user);

        try {
            app(CreatePsjbAction::class)->handle($user, [
                'sales_case_id' => $caseWithoutBi->id,
                'psjb_date' => '2026-09-02',
            ]);
            $this->fail('PSJB without BI should have been rejected.');
        } catch (ValidationException) {
            $this->assertDatabaseMissing('psjbs', ['sales_case_id' => $caseWithoutBi->id]);
        }

        $caseWithReviewBi = $this->activeCase($user);
        app(RecordBiCheckAction::class)->handle($user, [
            'sales_case_id' => $caseWithReviewBi->id,
            'check_date' => '2026-09-01',
            'result' => BiCheckResult::Review,
        ]);

        $this->expectException(ValidationException::class);

        app(CreatePsjbAction::class)->handle($user, [
            'sales_case_id' => $caseWithReviewBi->id,
            'psjb_date' => '2026-09-02',
        ]);
    }

    public function test_psjb_creation_sets_active_status_and_pemberkasan_stage(): void
    {
        $user = $this->hqAdmin();
        $case = $this->activeCase($user);
        $this->clearBi($user, $case);

        $psjb = app(CreatePsjbAction::class)->handle($user, [
            'sales_case_id' => $case->id,
            'psjb_date' => '2026-09-02',
            'document_number' => 'PSJB-0001',
        ]);

        $this->assertTrue($psjb->status === PsjbStatus::Active);
        $this->assertSame($case->id, $psjb->sales_case_id);
        $this->assertSame($user->id, $psjb->created_by);
        $this->assertTrue($case->refresh()->current_stage === SalesCaseStage::Pemberkasan);
    }

    public function test_psjb_coordinator_defaults_from_case_and_stays_snapshot(): void
    {
        $user = $this->hqAdmin();
        $caseCoordinator = User::factory()->create();
        $case = $this->activeCase($user, null, null, $caseCoordinator);
        $this->clearBi($user, $case);

        $psjb = app(CreatePsjbAction::class)->handle($user, [
            'sales_case_id' => $case->id,
            'psjb_date' => '2026-09-02',
        ]);

        $this->assertSame($caseCoordinator->id, $psjb->coordinator_id);

        $newCoordinator = User::factory()->create();
        $case->update(['coordinator_id' => $newCoordinator->id]);

        $this->assertSame($caseCoordinator->id, $psjb->refresh()->coordinator_id, 'Old PSJB snapshot must not be rewritten.');
    }

    public function test_second_active_psjb_for_case_is_rejected(): void
    {
        $user = $this->hqAdmin();
        $case = $this->activeCase($user);
        $this->clearBi($user, $case);

        app(CreatePsjbAction::class)->handle($user, [
            'sales_case_id' => $case->id,
            'psjb_date' => '2026-09-02',
            'document_number' => 'PSJB-0001',
        ]);

        $this->expectException(ValidationException::class);

        app(CreatePsjbAction::class)->handle($user, [
            'sales_case_id' => $case->id,
            'psjb_date' => '2026-09-03',
            'document_number' => 'PSJB-0002',
        ]);
    }

    public function test_second_active_psjb_is_blocked_by_database_constraint(): void
    {
        $user = $this->hqAdmin();
        $case = $this->activeCase($user);
        $this->clearBi($user, $case);

        app(CreatePsjbAction::class)->handle($user, [
            'sales_case_id' => $case->id,
            'psjb_date' => '2026-09-02',
        ]);

        $this->expectException(UniqueConstraintViolationException::class);

        Psjb::create([
            'sales_case_id' => $case->id,
            'psjb_date' => '2026-09-03',
            'status' => PsjbStatus::Active,
            'created_by' => $user->id,
        ]);
    }

    public function test_reissue_supersedes_old_psjb_and_creates_new_active(): void
    {
        $user = $this->hqAdmin();
        $case = $this->activeCase($user);
        $this->clearBi($user, $case);

        $oldPsjb = app(CreatePsjbAction::class)->handle($user, [
            'sales_case_id' => $case->id,
            'psjb_date' => '2026-09-02',
            'document_number' => 'PSJB-0001',
        ]);

        $newPsjb = app(ReissuePsjbAction::class)->handle($user, $case, [
            'psjb_date' => '2026-09-10',
            'document_number' => 'PSJB-0002',
        ]);

        $this->assertTrue($oldPsjb->refresh()->status === PsjbStatus::Superseded);
        $this->assertTrue($newPsjb->status === PsjbStatus::Active);
        $this->assertSame($case->id, $newPsjb->sales_case_id);
        $this->assertSame('PSJB-0001', $oldPsjb->refresh()->document_number, 'Old PSJB document must stay untouched.');
        $this->assertSame(2, Psjb::query()->whereBelongsTo($case)->count());
        $this->assertTrue($case->refresh()->current_stage === SalesCaseStage::Pemberkasan);
    }

    public function test_reissue_does_not_regress_case_beyond_pemberkasan(): void
    {
        $user = $this->hqAdmin();
        $case = $this->activeCase($user);
        $this->clearBi($user, $case);

        app(CreatePsjbAction::class)->handle($user, [
            'sales_case_id' => $case->id,
            'psjb_date' => '2026-09-02',
        ]);
        $case->update(['current_stage' => SalesCaseStage::ProsesBank]);

        app(ReissuePsjbAction::class)->handle($user, $case, [
            'psjb_date' => '2026-09-10',
            'document_number' => 'PSJB-0002',
        ]);

        $this->assertTrue($case->refresh()->current_stage === SalesCaseStage::ProsesBank);
    }

    public function test_reissue_without_active_psjb_is_rejected(): void
    {
        $user = $this->hqAdmin();
        $case = $this->activeCase($user);
        $this->clearBi($user, $case);

        $this->expectException(ValidationException::class);

        app(ReissuePsjbAction::class)->handle($user, $case, [
            'psjb_date' => '2026-09-10',
        ]);
    }

    public function test_cancel_returns_case_to_psjb_stage_and_keeps_case_active(): void
    {
        $user = $this->hqAdmin();
        $case = $this->activeCase($user);
        $this->clearBi($user, $case);

        $psjb = app(CreatePsjbAction::class)->handle($user, [
            'sales_case_id' => $case->id,
            'psjb_date' => '2026-09-02',
        ]);

        $cancelled = app(CancelPsjbAction::class)->handle($user, $psjb);

        $this->assertTrue($cancelled->status === PsjbStatus::Cancelled);

        $case = $case->refresh();
        $this->assertTrue($case->case_status === SalesCaseStatus::Active);
        $this->assertTrue($case->current_stage === SalesCaseStage::Psjb);
        $this->assertNull($case->closed_at);
    }

    public function test_cancel_is_blocked_when_case_has_progressed_beyond_pemberkasan(): void
    {
        $user = $this->hqAdmin();
        $case = $this->activeCase($user);
        $this->clearBi($user, $case);

        $psjb = app(CreatePsjbAction::class)->handle($user, [
            'sales_case_id' => $case->id,
            'psjb_date' => '2026-09-02',
        ]);

        // Simulates a later-phase state (downstream process records exist).
        $case->update(['current_stage' => SalesCaseStage::ProsesBank]);

        $this->expectException(ValidationException::class);

        app(CancelPsjbAction::class)->handle($user, $psjb);
    }

    public function test_cancelled_psjb_allows_new_psjb_creation(): void
    {
        $user = $this->hqAdmin();
        $case = $this->activeCase($user);
        $this->clearBi($user, $case);

        $psjb = app(CreatePsjbAction::class)->handle($user, [
            'sales_case_id' => $case->id,
            'psjb_date' => '2026-09-02',
        ]);
        app(CancelPsjbAction::class)->handle($user, $psjb);

        $newPsjb = app(CreatePsjbAction::class)->handle($user, [
            'sales_case_id' => $case->id,
            'psjb_date' => '2026-09-15',
            'document_number' => 'PSJB-0002',
        ]);

        $this->assertTrue($newPsjb->status === PsjbStatus::Active);
        $this->assertTrue($case->refresh()->current_stage === SalesCaseStage::Pemberkasan);
    }

    public function test_psjb_records_are_isolated_across_cases_of_the_same_consumer(): void
    {
        $user = $this->hqAdmin();
        $branch = Branch::factory()->create();
        $iwan = Consumer::factory()->create(['name' => 'Iwan']);

        $caseF13 = $this->activeCase($user, $branch, $iwan);
        $this->clearBi($user, $caseF13);
        app(CreatePsjbAction::class)->handle($user, [
            'sales_case_id' => $caseF13->id,
            'psjb_date' => '2026-09-02',
        ]);
        app(MarkSalesCaseMundurAction::class)->handle($user, $caseF13, 'Pindah ke kavling lain');

        $caseK03 = $this->activeCase($user, $branch, $iwan);

        $this->assertSame(1, Psjb::query()->whereBelongsTo($caseF13)->count());
        $this->assertSame(0, Psjb::query()->whereBelongsTo($caseK03)->count());

        // K03 case still needs its own CLEAR BI before its own PSJB.
        $this->expectException(ValidationException::class);

        app(CreatePsjbAction::class)->handle($user, [
            'sales_case_id' => $caseK03->id,
            'psjb_date' => '2026-09-03',
        ]);
    }

    public function test_forged_cross_branch_sales_case_id_is_blocked_for_psjb(): void
    {
        $otherBranch = Branch::factory()->create();
        $hq = $this->hqAdmin();

        $otherCase = $this->activeCase($hq, $otherBranch);
        $this->clearBi($hq, $otherCase);

        $ownBranchAdmin = $this->branchAdmin(Branch::factory()->create());

        $this->expectException(ValidationException::class);

        app(CreatePsjbAction::class)->handle($ownBranchAdmin, [
            'sales_case_id' => $otherCase->id,
            'psjb_date' => '2026-09-02',
        ]);
    }

    public function test_branch_admin_psjb_list_is_scoped_to_own_branch(): void
    {
        $ownBranch = Branch::factory()->create();
        $otherBranch = Branch::factory()->create();
        $hq = $this->hqAdmin();

        $ownCase = $this->activeCase($hq, $ownBranch);
        $otherCase = $this->activeCase($hq, $otherBranch);
        $this->clearBi($hq, $ownCase);
        $this->clearBi($hq, $otherCase);

        $ownPsjb = app(CreatePsjbAction::class)->handle($hq, ['sales_case_id' => $ownCase->id, 'psjb_date' => '2026-09-02']);
        $otherPsjb = app(CreatePsjbAction::class)->handle($hq, ['sales_case_id' => $otherCase->id, 'psjb_date' => '2026-09-02']);

        $this->actingAs($this->branchAdmin($ownBranch));

        Livewire::test(ListPsjbs::class)
            ->assertCanSeeTableRecords([$ownPsjb])
            ->assertCanNotSeeTableRecords([$otherPsjb]);
    }

    public function test_psjb_record_cannot_be_reassigned_to_another_case(): void
    {
        $user = $this->hqAdmin();
        $case = $this->activeCase($user);
        $this->clearBi($user, $case);

        $psjb = app(CreatePsjbAction::class)->handle($user, [
            'sales_case_id' => $case->id,
            'psjb_date' => '2026-09-02',
        ]);

        $this->assertFalse(Gate::forUser($user)->allows('update', $psjb));
    }

    public function test_psjb_filament_create_page_creates_psjb(): void
    {
        $user = $this->hqAdmin();
        $case = $this->activeCase($user);
        $this->clearBi($user, $case);

        $this->actingAs($user);

        Livewire::test(CreatePsjb::class)
            ->fillForm([
                'sales_case_id' => $case->id,
                'psjb_date' => '2026-09-02',
                'document_number' => 'PSJB-0001',
            ])
            ->call('create')
            ->assertHasNoFormErrors();

        $this->assertDatabaseHas('psjbs', [
            'sales_case_id' => $case->id,
            'document_number' => 'PSJB-0001',
            'status' => PsjbStatus::Active->value,
        ]);
        $this->assertTrue($case->refresh()->current_stage === SalesCaseStage::Pemberkasan);
    }
}
