<?php

namespace Tests\Feature;

use App\Actions\CancelPsjbAction;
use App\Actions\CreateSalesCaseAction;
use App\Actions\MarkSalesCaseMundurAction;
use App\Actions\MarkSalesCaseRejectedAction;
use App\Actions\MoveSalesCaseUnitAction;
use App\Filament\Resources\SalesCases\Pages\ViewSalesCase;
use App\FinancingType;
use App\Models\Branch;
use App\Models\Consumer;
use App\Models\DeveloperPpjb;
use App\Models\Project;
use App\Models\Psjb;
use App\Models\SalesCase;
use App\Models\Unit;
use App\Models\User;
use App\SalesCaseStatus;
use App\UnitStatus;
use App\UserRole;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Validation\ValidationException;
use Livewire\Livewire;
use Tests\TestCase;

class PhaseTwoCaseWorkflowTest extends TestCase
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

    private function makeUnit(Branch $branch): Unit
    {
        return Unit::factory()->for(Project::factory()->for($branch))->create();
    }

    private function activeCase(User $user, ?Branch $branch = null): SalesCase
    {
        $branch ??= Branch::factory()->create();

        return app(CreateSalesCaseAction::class)->handle($user, [
            'unit_id' => $this->makeUnit($branch)->id,
            'financing_type' => FinancingType::KprSubsidi,
            'consumer_id' => Consumer::factory()->create()->id,
            'source' => 'Kantor Cabang',
            'sales_pic_id' => User::factory()->create()->id,
            'coordinator_id' => User::factory()->create()->id,
        ]);
    }

    public function test_mundur_closes_case_and_releases_unit(): void
    {
        $user = $this->hqAdmin();
        $case = $this->activeCase($user);

        $closed = app(MarkSalesCaseMundurAction::class)->handle($user, $case, 'Konsumen tidak lanjut');

        $this->assertTrue($closed->case_status === SalesCaseStatus::Mundur);
        $this->assertNotNull($closed->closed_at);
        $this->assertSame('Konsumen tidak lanjut', $closed->closed_reason);
        $this->assertSame($case->consumer_id, $closed->consumer_id);
        $this->assertSame($case->unit_id, $closed->unit_id);
        $this->assertSame(UnitStatus::Tersedia->value, $closed->unit()->first()->status->value);
    }

    public function test_reject_closes_case_and_releases_unit(): void
    {
        $user = $this->hqAdmin();
        $case = $this->activeCase($user);

        $closed = app(MarkSalesCaseRejectedAction::class)->handle($user, $case, 'Syarat tidak terpenuhi');

        $this->assertTrue($closed->case_status === SalesCaseStatus::Reject);
        $this->assertNotNull($closed->closed_at);
        $this->assertSame(UnitStatus::Tersedia->value, $closed->unit()->first()->status->value);
    }

    public function test_closing_a_non_active_case_is_rejected(): void
    {
        $user = $this->hqAdmin();
        $case = $this->activeCase($user);

        app(MarkSalesCaseMundurAction::class)->handle($user, $case, 'Konsumen tidak lanjut');

        $this->expectException(ValidationException::class);

        app(MarkSalesCaseMundurAction::class)->handle($user, $case->refresh(), 'Coba tutup lagi');
    }

    public function test_pindah_kavling_moves_case_in_place_and_keeps_it_active(): void
    {
        $user = $this->hqAdmin();
        $branch = Branch::factory()->create();
        $oldCase = $this->activeCase($user, $branch);
        $oldUnitId = $oldCase->unit_id;
        $oldUnitCode = $oldCase->unit->unit_code;
        $newUnit = $this->makeUnit($branch);

        $moved = app(MoveSalesCaseUnitAction::class)->handle($user, $oldCase->refresh(), $newUnit->id, 'Konsumen minta kavling dekat jalan');

        $this->assertSame($oldCase->id, $moved->id, 'A unit transfer must not create a new sales case.');
        $this->assertTrue($moved->case_status === SalesCaseStatus::Active);
        $this->assertNull($moved->closed_at);
        $this->assertSame($newUnit->id, $moved->unit_id);
        $this->assertSame($newUnit->project_id, $moved->project_id);
        $this->assertSame($branch->id, $moved->branch_id);
        $this->assertSame($oldCase->consumer_id, $moved->consumer_id);
        $this->assertSame($oldCase->financing_type, $moved->financing_type);
        $this->assertSame($oldCase->source, $moved->source);
        $this->assertSame($oldCase->sales_pic_id, $moved->sales_pic_id);
        $this->assertSame($oldCase->coordinator_id, $moved->coordinator_id);
        $this->assertSame('Konsumen minta kavling dekat jalan', $moved->transfer_reason);

        $this->assertSame(UnitStatus::Tersedia->value, Unit::find($oldUnitId)->status->value);
        $this->assertSame(UnitStatus::Booking->value, $newUnit->fresh()->status->value);

        $this->assertSame(1, SalesCase::query()->whereBelongsTo($oldCase->consumer)->count());
        $this->assertSame(1, Consumer::query()->count());

        $this->assertDatabaseHas('case_notes', [
            'sales_case_id' => $moved->id,
        ]);
        $note = $moved->caseNotes()->latest('id')->first();
        $this->assertStringContainsString($oldUnitCode, $note->note);
        $this->assertStringContainsString($newUnit->unit_code, $note->note);
    }

    public function test_waiting_list_case_can_be_assigned_a_unit_in_place(): void
    {
        $user = $this->hqAdmin();
        $branch = Branch::factory()->create();
        $project = Project::factory()->for($branch)->create();
        $case = app(CreateSalesCaseAction::class)->handle($user, [
            'project_id' => $project->id,
            'unit_id' => null,
            'financing_type' => FinancingType::KprSubsidi,
            'consumer_id' => Consumer::factory()->create()->id,
        ]);
        $newUnit = Unit::factory()->for($project)->create();

        $assigned = app(MoveSalesCaseUnitAction::class)->handle($user, $case, $newUnit->id, 'Unit tersedia');

        $this->assertSame($case->id, $assigned->id);
        $this->assertTrue($assigned->case_status === SalesCaseStatus::Active);
        $this->assertSame($newUnit->id, $assigned->unit_id);
        $this->assertSame(UnitStatus::Booking->value, $newUnit->fresh()->status->value);
        $this->assertStringContainsString("Penempatan kavling ke {$newUnit->unit_code}", $assigned->caseNotes()->firstOrFail()->note);
    }

    public function test_initial_assignment_is_blocked_after_developer_ppjb(): void
    {
        $user = $this->hqAdmin();
        $branch = Branch::factory()->create();
        $project = Project::factory()->for($branch)->create();
        $case = SalesCase::factory()->create(['unit_id' => null, 'project_id' => $project->id, 'branch_id' => $branch->id]);
        $ppjb = DeveloperPpjb::factory()->create(['sales_case_id' => $case->id]);
        $unit = Unit::factory()->for($project)->create();

        try {
            app(MoveSalesCaseUnitAction::class)->handle($user, $case, $unit->id, 'Penempatan');
            $this->fail('Initial assignment after PPJB unexpectedly succeeded.');
        } catch (ValidationException $exception) {
            $this->assertStringContainsString('PPJB Developer', $exception->getMessage());
        }

        $this->assertNull($case->refresh()->unit_id);
        $this->assertSame(UnitStatus::Tersedia, $unit->fresh()->status);
        $this->assertSame($case->id, $ppjb->refresh()->sales_case_id);
    }

    public function test_branch_admin_cannot_cancel_other_branch_psjb(): void
    {
        $branchA = Branch::factory()->create();
        $branchB = Branch::factory()->create();
        $admin = User::factory()->create(['branch_id' => $branchA->id]);
        $admin->assignRole(UserRole::BranchAdmin);
        $case = $this->activeCase($this->hqAdmin(), $branchB);
        $psjb = Psjb::factory()->create(['sales_case_id' => $case->id]);
        $stage = $case->current_stage;

        try {
            app(CancelPsjbAction::class)->handle($admin, $psjb);
            $this->fail('Cross-branch PSJB cancellation unexpectedly succeeded.');
        } catch (ValidationException $exception) {
            $this->assertStringContainsString('luar cabang', $exception->getMessage());
        }

        $this->assertSame('ACTIVE', $psjb->refresh()->status->value);
        $this->assertSame($stage, $case->refresh()->current_stage);
    }

    public function test_active_psjb_blocks_move_until_cancelled(): void
    {
        $user = $this->hqAdmin();
        $branch = Branch::factory()->create();
        $case = $this->activeCase($user, $branch);
        $oldUnit = $case->unit;
        $newUnit = $this->makeUnit($branch);
        $psjb = Psjb::factory()->create(['sales_case_id' => $case->id]);

        try {
            app(MoveSalesCaseUnitAction::class)->handle($user, $case, $newUnit->id, 'Pindah');
            $this->fail('Move with active PSJB unexpectedly succeeded.');
        } catch (ValidationException $exception) {
            $this->assertStringContainsString('PSJB aktif', $exception->getMessage());
        }

        $this->assertSame($oldUnit->id, $case->refresh()->unit_id);
        $this->assertSame(UnitStatus::Booking, $oldUnit->fresh()->status);
        $this->assertSame(UnitStatus::Tersedia, $newUnit->fresh()->status);

        app(CancelPsjbAction::class)->handle($user, $psjb);
        $moved = app(MoveSalesCaseUnitAction::class)->handle($user, $case, $newUnit->id, 'Pindah aman');

        $this->assertSame($case->id, $moved->id);
        $this->assertSame($newUnit->id, $moved->unit_id);
        $this->assertSame('CANCELLED', $psjb->refresh()->status->value);
    }

    public function test_pindah_kavling_rejects_non_active_case(): void
    {
        $user = $this->hqAdmin();
        $branch = Branch::factory()->create();
        $case = $this->activeCase($user, $branch);

        app(MarkSalesCaseMundurAction::class)->handle($user, $case, 'Tidak lanjut');

        $this->expectException(ValidationException::class);

        app(MoveSalesCaseUnitAction::class)->handle($user, $case->refresh(), $this->makeUnit($branch)->id, 'Pindah');
    }

    public function test_pindah_kavling_rejects_occupied_target_unit(): void
    {
        $user = $this->hqAdmin();
        $branch = Branch::factory()->create();
        $case = $this->activeCase($user, $branch);
        $occupiedUnit = $this->makeUnit($branch);

        $this->createCaseForUnit($user, $occupiedUnit);

        $this->expectException(ValidationException::class);

        app(MoveSalesCaseUnitAction::class)->handle($user, $case->refresh(), $occupiedUnit->id, 'Pindah');
    }

    public function test_pindah_kavling_rejects_same_unit(): void
    {
        $user = $this->hqAdmin();
        $case = $this->activeCase($user);

        $this->expectException(ValidationException::class);

        app(MoveSalesCaseUnitAction::class)->handle($user, $case->refresh(), $case->unit_id, 'Pindah');
    }

    public function test_pindah_kavling_rejects_cross_branch_unit(): void
    {
        $user = $this->hqAdmin();
        $case = $this->activeCase($user, Branch::factory()->create());
        $crossBranchUnit = $this->makeUnit(Branch::factory()->create());

        $this->expectException(ValidationException::class);

        app(MoveSalesCaseUnitAction::class)->handle($user, $case->refresh(), $crossBranchUnit->id, 'Pindah');
    }

    public function test_same_unit_supports_new_consumer_after_mundur(): void
    {
        $user = $this->hqAdmin();
        $branch = Branch::factory()->create();
        $unit = $this->makeUnit($branch);
        $sri = Consumer::factory()->create(['name' => 'Sri Wahyuni']);

        $caseSri = app(CreateSalesCaseAction::class)->handle($user, [
            'unit_id' => $unit->id,
            'financing_type' => FinancingType::KprSubsidi,
            'consumer_id' => $sri->id,
        ]);

        app(MarkSalesCaseMundurAction::class)->handle($user, $caseSri, 'Sri mundur');

        $budi = Consumer::factory()->create(['name' => 'Budi Santoso']);

        $caseBudi = app(CreateSalesCaseAction::class)->handle($user, [
            'unit_id' => $unit->id,
            'financing_type' => FinancingType::KprSubsidi,
            'consumer_id' => $budi->id,
        ]);

        $this->assertSame(2, SalesCase::query()->whereBelongsTo($unit)->count());
        $this->assertTrue($caseBudi->case_status === SalesCaseStatus::Active);
        $this->assertTrue($caseSri->refresh()->case_status === SalesCaseStatus::Mundur);
    }

    public function test_same_consumer_supports_new_unit_after_close(): void
    {
        $user = $this->hqAdmin();
        $branch = Branch::factory()->create();
        $sri = Consumer::factory()->create();

        $caseK20 = app(CreateSalesCaseAction::class)->handle($user, [
            'unit_id' => $this->makeUnit($branch)->id,
            'financing_type' => FinancingType::KprSubsidi,
            'consumer_id' => $sri->id,
        ]);

        app(MarkSalesCaseMundurAction::class)->handle($user, $caseK20, 'Tidak lanjut');

        $caseK15 = app(CreateSalesCaseAction::class)->handle($user, [
            'unit_id' => $this->makeUnit($branch)->id,
            'financing_type' => FinancingType::KprSubsidi,
            'consumer_id' => $sri->id,
        ]);

        $this->assertSame(2, SalesCase::query()->whereBelongsTo($sri)->count());
        $this->assertSame(1, Consumer::query()->count());
        $this->assertNull($caseK15->previous_case_id);
    }

    public function test_consumer_identity_remains_single_across_branches(): void
    {
        $user = $this->hqAdmin();
        $sri = Consumer::factory()->create();

        $caseA = app(CreateSalesCaseAction::class)->handle($user, [
            'unit_id' => $this->makeUnit(Branch::factory()->create())->id,
            'financing_type' => FinancingType::KprSubsidi,
            'consumer_id' => $sri->id,
        ]);
        app(MarkSalesCaseMundurAction::class)->handle($user, $caseA, 'Pindah domisili');

        app(CreateSalesCaseAction::class)->handle($user, [
            'unit_id' => $this->makeUnit(Branch::factory()->create())->id,
            'financing_type' => FinancingType::KprSubsidi,
            'consumer_id' => $sri->id,
        ]);

        $this->assertSame(1, Consumer::query()->count());
        $this->assertSame(2, SalesCase::query()->whereBelongsTo($sri)->count());
    }

    public function test_view_page_renders_case_detail_after_unit_move(): void
    {
        $user = $this->hqAdmin();
        $branch = Branch::factory()->create();
        $oldCase = $this->activeCase($user, $branch);
        $moved = app(MoveSalesCaseUnitAction::class)->handle(
            $user,
            $oldCase->refresh(),
            $this->makeUnit($branch)->id,
            'Minta kavling hook',
        );

        $this->assertSame($oldCase->id, $moved->id);

        $this->actingAs($user);

        $this->get("/admin/sales-cases/{$moved->id}")
            ->assertOk()
            ->assertSeeText($moved->consumer->name);

        Livewire::test(ViewSalesCase::class, ['record' => $moved->id])
            ->assertSuccessful();
    }

    private function createCaseForUnit(User $user, Unit $unit): SalesCase
    {
        return app(CreateSalesCaseAction::class)->handle($user, [
            'unit_id' => $unit->id,
            'financing_type' => FinancingType::KprSubsidi,
            'consumer_id' => Consumer::factory()->create()->id,
        ]);
    }
}
