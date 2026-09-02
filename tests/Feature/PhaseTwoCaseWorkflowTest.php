<?php

namespace Tests\Feature;

use App\Actions\CancelSalesCaseAction;
use App\Actions\CreateSalesCaseAction;
use App\Actions\MarkSalesCaseMundurAction;
use App\Actions\MarkSalesCaseRejectedAction;
use App\Actions\MoveSalesCaseUnitAction;
use App\Filament\Resources\SalesCases\Pages\ViewSalesCase;
use App\FinancingType;
use App\Models\Branch;
use App\Models\Consumer;
use App\Models\Project;
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

    public function test_cancel_closes_case_and_releases_unit(): void
    {
        $user = $this->hqAdmin();
        $case = $this->activeCase($user);

        $closed = app(CancelSalesCaseAction::class)->handle($user, $case);

        $this->assertTrue($closed->case_status === SalesCaseStatus::Cancelled);
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

    public function test_pindah_kavling_closes_old_case_and_creates_linked_new_case(): void
    {
        $user = $this->hqAdmin();
        $branch = Branch::factory()->create();
        $oldCase = $this->activeCase($user, $branch);
        $oldUnitId = $oldCase->unit_id;
        $newUnit = $this->makeUnit($branch);

        $newCase = app(MoveSalesCaseUnitAction::class)->handle($user, $oldCase->refresh(), $newUnit->id, 'Konsumen minta kavling dekat jalan');

        $oldCase = $oldCase->refresh();

        $this->assertTrue($oldCase->case_status === SalesCaseStatus::PindahKavling);
        $this->assertNotNull($oldCase->closed_at);
        $this->assertSame($oldUnitId, $oldCase->unit_id, 'Old case identity must never be mutated.');

        $this->assertTrue($newCase->case_status === SalesCaseStatus::Active);
        $this->assertSame($oldCase->id, $newCase->previous_case_id);
        $this->assertSame($newUnit->id, $newCase->unit_id);
        $this->assertSame($newUnit->project_id, $newCase->project_id);
        $this->assertSame($branch->id, $newCase->branch_id);
        $this->assertSame($oldCase->consumer_id, $newCase->consumer_id);
        $this->assertSame($oldCase->financing_type, $newCase->financing_type);
        $this->assertSame($oldCase->source, $newCase->source);
        $this->assertSame($oldCase->sales_pic_id, $newCase->sales_pic_id);
        $this->assertSame($oldCase->coordinator_id, $newCase->coordinator_id);
        $this->assertSame('Konsumen minta kavling dekat jalan', $newCase->transfer_reason);

        $this->assertSame(UnitStatus::Tersedia->value, Unit::find($oldUnitId)->status->value);
        $this->assertSame(UnitStatus::Booking->value, $newUnit->fresh()->status->value);

        $this->assertSame(2, SalesCase::query()->whereBelongsTo($oldCase->consumer)->count());
        $this->assertSame(1, Consumer::query()->count());
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

        app(CancelSalesCaseAction::class)->handle($user, $caseK20);

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

    public function test_view_page_renders_case_detail_with_history_link(): void
    {
        $user = $this->hqAdmin();
        $branch = Branch::factory()->create();
        $oldCase = $this->activeCase($user, $branch);
        $newCase = app(MoveSalesCaseUnitAction::class)->handle(
            $user,
            $oldCase->refresh(),
            $this->makeUnit($branch)->id,
            'Minta kavling hook',
        );

        $this->actingAs($user);

        $this->get("/admin/sales-cases/{$newCase->id}")
            ->assertOk()
            ->assertSeeText($newCase->consumer->name);

        Livewire::test(ViewSalesCase::class, ['record' => $newCase->id])
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
