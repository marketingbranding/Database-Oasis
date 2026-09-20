<?php

namespace Tests\Feature;

use App\Actions\CreateAkadAction;
use App\Actions\CreateBastAction;
use App\Actions\CreateDeveloperPpjbAction;
use App\Actions\CreatePsjbAction;
use App\Actions\CreateSalesCaseAction;
use App\Actions\MarkSalesCaseMundurAction;
use App\Actions\MarkSalesCaseRejectedAction;
use App\Actions\RecordBiCheckAction;
use App\Actions\ReissueDeveloperPpjbAction;
use App\BiCheckResult;
use App\FinancingType;
use App\Models\Branch;
use App\Models\Consumer;
use App\Models\DeveloperPpjb;
use App\Models\Project;
use App\Models\SalesCase;
use App\Models\Unit;
use App\Models\User;
use App\SalesCaseStatus;
use App\Services\MagelangImport\MagelangImporter;
use App\Services\UnitStatusResolver;
use App\UnitStatus;
use App\UserRole;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class UnitStatusAvailabilityTest extends TestCase
{
    use RefreshDatabase;

    private User $user;

    private Unit $unit;

    private UnitStatusResolver $resolver;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed();
        $this->user = User::factory()->create();
        $this->user->assignRole(UserRole::HqAdmin);
        $this->unit = Unit::factory()->for(Project::factory()->for(Branch::factory()))->create();
        $this->resolver = app(UnitStatusResolver::class);
    }

    public function test_resolver_uses_akad_precedence_and_releases_non_sold_history(): void
    {
        $case = SalesCase::factory()->forUnit($this->unit)->create();
        $this->assertSame(UnitStatus::Booking, $this->resolver->resolve($this->unit));
        $this->unit->update(['status' => UnitStatus::Tersedia]);
        $this->assertFalse(Unit::available()->whereKey($this->unit->id)->exists());
        $this->unit->salesCases()->first()->update(['case_status' => SalesCaseStatus::Mundur]);
        $this->assertSame(UnitStatus::Tersedia, $this->resolver->reconcile($this->unit)->status);
        $this->assertTrue(Unit::available()->whereKey($this->unit->id)->exists());
        $this->assertNotNull($case);
    }

    public function test_akad_makes_active_unit_sold_and_unavailable(): void
    {
        $case = SalesCase::factory()->forUnit($this->unit)->create();
        $ppjb = DeveloperPpjb::factory()->create(['sales_case_id' => $case->id]);
        $case->akad()->create(['developer_ppjb_id' => $ppjb->id, 'akad_date' => now(), 'created_by' => $this->user->id]);

        $this->assertSame(UnitStatus::Terjual, $this->resolver->reconcile($this->unit)->status);
        $this->assertFalse(Unit::available()->whereKey($this->unit->id)->exists());
    }

    public function test_waiting_list_can_complete_bi_and_psjb_before_assignment(): void
    {
        $case = SalesCase::factory()->create([
            'unit_id' => null,
            'project_id' => $this->unit->project_id,
            'branch_id' => $this->unit->project->branch_id,
            'financing_type' => FinancingType::KprSubsidi,
        ]);

        app(RecordBiCheckAction::class)->handle($this->user, [
            'sales_case_id' => $case->id,
            'check_date' => now()->toDateString(),
            'result' => BiCheckResult::Clear,
        ]);
        $psjb = app(CreatePsjbAction::class)->handle($this->user, ['sales_case_id' => $case->id, 'psjb_date' => now()->toDateString()]);

        $this->assertNull($case->refresh()->unit_id);
        $this->assertNotNull($psjb);
    }

    public function test_waiting_list_cannot_create_developer_ppjb_until_assigned(): void
    {
        $case = SalesCase::factory()->create([
            'unit_id' => null,
            'project_id' => $this->unit->project_id,
            'branch_id' => $this->unit->project->branch_id,
            'financing_type' => FinancingType::KprSubsidi,
        ]);

        $this->expectException(ValidationException::class);
        app(CreateDeveloperPpjbAction::class)->handle($this->user, ['sales_case_id' => $case->id, 'document_date' => now()->toDateString()]);
    }

    public function test_waiting_list_can_reject_without_unit(): void
    {
        $case = SalesCase::factory()->create(['unit_id' => null, 'project_id' => $this->unit->project_id, 'branch_id' => $this->unit->project->branch_id, 'financing_type' => FinancingType::KprSubsidi]);
        $closed = app(MarkSalesCaseRejectedAction::class)->handle($this->user, $case, 'Tidak memenuhi syarat');
        $this->assertSame(SalesCaseStatus::Reject, $closed->case_status);
        $this->assertNotNull($closed->closed_at);
        $this->assertNull($closed->unit_id);
    }

    public function test_historical_cancelled_status_remains_castable_and_current_statuses_are_operational_only(): void
    {
        $case = SalesCase::factory()->create(['case_status' => SalesCaseStatus::Cancelled]);
        $this->assertSame(SalesCaseStatus::Cancelled, $case->refresh()->case_status);
        $this->assertSame([SalesCaseStatus::Active, SalesCaseStatus::Completed, SalesCaseStatus::Mundur, SalesCaseStatus::Reject], SalesCaseStatus::current());
    }

    public function test_waiting_list_can_mundur_without_unit(): void
    {
        $case = SalesCase::factory()->create([
            'unit_id' => null,
            'project_id' => $this->unit->project_id,
            'branch_id' => $this->unit->project->branch_id,
            'financing_type' => FinancingType::KprSubsidi,
        ]);

        $closed = app(MarkSalesCaseMundurAction::class)->handle($this->user, $case, 'Tidak lanjut');

        $this->assertSame(SalesCaseStatus::Mundur, $closed->case_status);
        $this->assertNull($closed->unit_id);
    }

    public function test_all_property_finalization_actions_reject_waiting_list_cases(): void
    {
        $case = SalesCase::factory()->create(['unit_id' => null, 'project_id' => $this->unit->project_id, 'branch_id' => $this->unit->project->branch_id, 'financing_type' => FinancingType::KprSubsidi]);
        $ppjb = DeveloperPpjb::factory()->create(['sales_case_id' => $case->id]);

        foreach ([
            fn () => app(CreateDeveloperPpjbAction::class)->handle($this->user, ['sales_case_id' => $case->id, 'document_date' => now()->toDateString()]),
            fn () => app(ReissueDeveloperPpjbAction::class)->handle($this->user, $case, ['document_date' => now()->toDateString()]),
            fn () => app(CreateAkadAction::class)->handle($this->user, ['sales_case_id' => $case->id, 'developer_ppjb_id' => $ppjb->id, 'akad_date' => now()->toDateString()]),
            fn () => app(CreateBastAction::class)->handle($this->user, ['sales_case_id' => $case->id, 'akad_id' => 'missing', 'bast_date' => now()->toDateString()]),
        ] as $attempt) {
            try {
                $attempt();
                $this->fail('Waiting List finalization unexpectedly succeeded.');
            } catch (ValidationException $exception) {
                $this->assertStringContainsString('kavling', strtolower($exception->getMessage()));
            }
        }
    }

    public function test_create_action_rejects_stale_booking_until_explicit_reconcile(): void
    {
        $this->unit->update(['status' => UnitStatus::Booking]);

        $this->expectException(ValidationException::class);
        app(CreateSalesCaseAction::class)->handle($this->user, [
            'unit_id' => $this->unit->id,
            'project_id' => $this->unit->project_id,
            'financing_type' => FinancingType::KprSubsidi,
            'consumer_id' => Consumer::factory()->create()->id,
        ]);
    }

    public function test_create_action_uses_available_unit_and_reconciles_booking(): void
    {
        $case = app(CreateSalesCaseAction::class)->handle($this->user, [
            'unit_id' => $this->unit->id,
            'project_id' => $this->unit->project_id,
            'financing_type' => FinancingType::KprSubsidi,
            'consumer_id' => Consumer::factory()->create()->id,
        ]);

        $this->assertSame($this->unit->id, $case->unit_id);
        $this->assertSame(UnitStatus::Booking, $this->unit->fresh()->status);
    }

    public function test_create_action_rejects_stale_terjual_unit_with_akad(): void
    {
        $case = SalesCase::factory()->forUnit($this->unit)->create();
        $ppjb = DeveloperPpjb::factory()->create(['sales_case_id' => $case->id]);
        $case->akad()->create(['developer_ppjb_id' => $ppjb->id, 'akad_date' => now(), 'created_by' => $this->user->id]);
        $this->unit->update(['status' => UnitStatus::Tersedia]);

        $this->expectException(ValidationException::class);
        app(CreateSalesCaseAction::class)->handle($this->user, [
            'unit_id' => $this->unit->id,
            'project_id' => $this->unit->project_id,
            'financing_type' => FinancingType::KprSubsidi,
            'consumer_id' => Consumer::factory()->create()->id,
        ]);
    }

    public function test_importer_refreshes_unused_units_only_in_its_branch(): void
    {
        $otherBranch = Branch::factory()->create();
        $stale = Unit::factory()->for(Project::factory()->for($this->unit->project->branch))->create(['status' => UnitStatus::Booking]);
        $other = Unit::factory()->for(Project::factory()->for($otherBranch))->create(['status' => UnitStatus::Terjual]);

        app(MagelangImporter::class, ['branch' => $this->unit->project->branch])->refreshUnitStatuses();

        $this->assertSame(UnitStatus::Tersedia, $stale->fresh()->status);
        $this->assertSame(UnitStatus::Terjual, $other->fresh()->status);
    }

    public function test_refresh_reconciles_duplicate_unit_codes_only_in_import_branch(): void
    {
        $branch = Branch::factory()->create();
        $otherBranch = Branch::factory()->create();
        $projectA = Project::factory()->for($branch)->create();
        $projectB = Project::factory()->for($branch)->create();
        $unitA = Unit::factory()->for($projectA)->create(['unit_code' => 'A01', 'status' => UnitStatus::Terjual]);
        $unitB = Unit::factory()->for($projectB)->create(['unit_code' => 'A01', 'status' => UnitStatus::Booking]);
        $otherUnit = Unit::factory()->for(Project::factory()->for($otherBranch))->create(['unit_code' => 'A01', 'status' => UnitStatus::Terjual]);
        SalesCase::factory()->forUnit($unitA)->create(['case_status' => SalesCaseStatus::Active]);

        app(MagelangImporter::class, ['branch' => $branch])->refreshUnitStatuses();

        $this->assertSame(UnitStatus::Booking, $unitA->fresh()->status);
        $this->assertSame(UnitStatus::Tersedia, $unitB->fresh()->status);
        $this->assertSame(UnitStatus::Terjual, $otherUnit->fresh()->status);
    }

    public function test_create_action_rejects_unavailable_unit_and_allows_waiting_list(): void
    {
        SalesCase::factory()->forUnit($this->unit)->create();

        $this->expectException(ValidationException::class);
        app(CreateSalesCaseAction::class)->handle($this->user, [
            'unit_id' => $this->unit->id,
            'project_id' => $this->unit->project_id,
            'financing_type' => FinancingType::KprSubsidi,
            'consumer_id' => Consumer::factory()->create()->id,
        ]);
    }
}
