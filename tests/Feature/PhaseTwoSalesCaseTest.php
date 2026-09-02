<?php

namespace Tests\Feature;

use App\Actions\CreateSalesCaseAction;
use App\Filament\Resources\SalesCases\Pages\CreateSalesCase;
use App\Filament\Resources\SalesCases\Pages\EditSalesCase;
use App\Filament\Resources\SalesCases\Pages\ListSalesCases;
use App\FinancingType;
use App\Models\Branch;
use App\Models\Consumer;
use App\Models\Project;
use App\Models\SalesCase;
use App\Models\Unit;
use App\Models\User;
use App\SalesCaseStage;
use App\SalesCaseStatus;
use App\UnitStatus;
use App\UserRole;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Validation\ValidationException;
use Livewire\Livewire;
use Tests\TestCase;

class PhaseTwoSalesCaseTest extends TestCase
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

    private function createCase(User $user, Unit $unit, array $extra = []): SalesCase
    {
        return app(CreateSalesCaseAction::class)->handle($user, array_merge([
            'unit_id' => $unit->id,
            'financing_type' => FinancingType::KprSubsidi,
            'consumer_id' => Consumer::factory()->create()->id,
        ], $extra));
    }

    public function test_create_action_creates_active_case_at_data_konsumen_stage(): void
    {
        $user = $this->hqAdmin();
        $unit = $this->makeUnit(Branch::factory()->create());

        $case = $this->createCase($user, $unit, ['source' => 'Website', 'booking_date' => '2026-09-01']);

        $this->assertTrue($case->case_status === SalesCaseStatus::Active);
        $this->assertTrue($case->current_stage === SalesCaseStage::DataKonsumen);
        $this->assertSame($user->id, $case->created_by);
        $this->assertSame('Website', $case->source);
    }

    public function test_create_action_sets_unit_status_to_booking(): void
    {
        $user = $this->hqAdmin();
        $unit = $this->makeUnit(Branch::factory()->create());
        $this->assertTrue($unit->status === UnitStatus::Tersedia);

        $this->createCase($user, $unit);

        $this->assertTrue($unit->fresh()->status === UnitStatus::Booking);
    }

    public function test_branch_and_project_are_derived_from_unit_not_from_input(): void
    {
        $user = $this->hqAdmin();
        $unit = $this->makeUnit(Branch::factory()->create());

        $otherBranch = Branch::factory()->create();
        $otherProject = Project::factory()->for($otherBranch)->create();

        $case = $this->createCase($user, $unit, [
            'branch_id' => $otherBranch->id,
            'project_id' => $otherProject->id,
        ]);

        $this->assertSame($unit->project_id, $case->project_id);
        $this->assertSame($unit->project->branch_id, $case->branch_id);
        $this->assertNotSame($otherBranch->id, $case->branch_id);
    }

    public function test_second_active_case_for_same_unit_is_rejected(): void
    {
        $user = $this->hqAdmin();
        $unit = $this->makeUnit(Branch::factory()->create());

        $this->createCase($user, $unit);

        $this->expectException(ValidationException::class);

        $this->createCase($user, $unit);
    }

    public function test_second_active_case_for_same_consumer_is_rejected(): void
    {
        $user = $this->hqAdmin();
        $branch = Branch::factory()->create();
        $consumer = Consumer::factory()->create();

        $this->createCase($user, $this->makeUnit($branch), ['consumer_id' => $consumer->id]);

        $this->expectException(ValidationException::class);

        $this->createCase($user, $this->makeUnit($branch), ['consumer_id' => $consumer->id]);
    }

    public function test_duplicate_active_case_for_unit_is_blocked_by_database_constraint(): void
    {
        $user = $this->hqAdmin();
        $unit = $this->makeUnit(Branch::factory()->create());
        $case = $this->createCase($user, $unit);

        $this->expectException(UniqueConstraintViolationException::class);

        SalesCase::create([
            'consumer_id' => Consumer::factory()->create()->id,
            'unit_id' => $case->unit_id,
            'project_id' => $case->project_id,
            'branch_id' => $case->branch_id,
            'financing_type' => FinancingType::KprSubsidi,
            'current_stage' => SalesCaseStage::DataKonsumen,
            'case_status' => SalesCaseStatus::Active,
            'created_by' => $user->id,
        ]);
    }

    public function test_duplicate_active_case_for_consumer_is_blocked_by_database_constraint(): void
    {
        $user = $this->hqAdmin();
        $branch = Branch::factory()->create();
        $consumer = Consumer::factory()->create();
        $case = $this->createCase($user, $this->makeUnit($branch), ['consumer_id' => $consumer->id]);

        $this->expectException(UniqueConstraintViolationException::class);

        SalesCase::create([
            'consumer_id' => $consumer->id,
            'unit_id' => $this->makeUnit($branch)->id,
            'project_id' => $case->project_id,
            'branch_id' => $case->branch_id,
            'financing_type' => FinancingType::KprSubsidi,
            'current_stage' => SalesCaseStage::DataKonsumen,
            'case_status' => SalesCaseStatus::Active,
            'created_by' => $user->id,
        ]);
    }

    public function test_closed_history_does_not_block_a_new_case_for_same_unit_and_consumer(): void
    {
        $user = $this->hqAdmin();
        $branch = Branch::factory()->create();
        $unit = $this->makeUnit($branch);
        $consumer = Consumer::factory()->create();

        $first = $this->createCase($user, $unit, ['consumer_id' => $consumer->id]);
        $first->update(['case_status' => SalesCaseStatus::Mundur, 'closed_at' => now()]);
        $unit->update(['status' => UnitStatus::Tersedia]);

        $second = $this->createCase($user, $unit, ['consumer_id' => $consumer->id]);

        $this->assertTrue($second->case_status === SalesCaseStatus::Active);
        $this->assertNotSame($first->id, $second->id);
    }

    public function test_new_consumer_is_not_created_when_case_creation_fails(): void
    {
        $user = $this->hqAdmin();
        $unit = $this->makeUnit(Branch::factory()->create());

        $this->createCase($user, $unit);

        try {
            app(CreateSalesCaseAction::class)->handle($user, [
                'unit_id' => $unit->id,
                'financing_type' => FinancingType::KprSubsidi,
                'consumer_attributes' => [
                    'nik' => '3325010101990002',
                    'name' => 'Budi Santoso',
                ],
            ]);
        } catch (ValidationException) {
            $this->assertDatabaseMissing('consumers', ['nik' => '3325010101990002']);

            return;
        }

        $this->fail('Expected ValidationException was not thrown.');
    }

    public function test_new_consumer_with_existing_nik_is_rejected_without_orphan(): void
    {
        $user = $this->hqAdmin();
        $consumer = Consumer::factory()->create();

        try {
            app(CreateSalesCaseAction::class)->handle($user, [
                'unit_id' => $this->makeUnit(Branch::factory()->create())->id,
                'financing_type' => FinancingType::KprSubsidi,
                'consumer_attributes' => [
                    'nik' => $consumer->nik,
                    'name' => 'Duplicate Person',
                ],
            ]);
        } catch (ValidationException) {
            $this->assertDatabaseCount('consumers', 1);

            return;
        }

        $this->fail('Expected ValidationException was not thrown.');
    }

    public function test_consumer_id_cannot_be_changed_through_the_edit_page(): void
    {
        $case = $this->createCase($this->hqAdmin(), $this->makeUnit(Branch::factory()->create()));
        $otherConsumer = Consumer::factory()->create();

        $this->actingAs($this->hqAdmin());

        Livewire::test(EditSalesCase::class, ['record' => $case->id])
            ->set('data.consumer_id', $otherConsumer->id)
            ->call('save')
            ->assertHasNoFormErrors();

        $this->assertSame($case->consumer_id, $case->fresh()->consumer_id);
    }

    public function test_unit_id_cannot_be_changed_through_the_edit_page(): void
    {
        $branch = Branch::factory()->create();
        $case = $this->createCase($this->hqAdmin(), $this->makeUnit($branch));
        $otherUnit = $this->makeUnit($branch);

        $this->actingAs($this->hqAdmin());

        Livewire::test(EditSalesCase::class, ['record' => $case->id])
            ->set('data.unit_id', $otherUnit->id)
            ->call('save')
            ->assertHasNoFormErrors();

        $this->assertSame($case->unit_id, $case->fresh()->unit_id);
        $this->assertSame($case->project_id, $case->fresh()->project_id);
        $this->assertSame($case->branch_id, $case->fresh()->branch_id);
    }

    public function test_edit_page_allows_non_identity_field_updates(): void
    {
        $branch = Branch::factory()->create();
        $case = $this->createCase($this->hqAdmin(), $this->makeUnit($branch), ['source' => 'Lama']);

        $this->actingAs($this->hqAdmin());

        Livewire::test(EditSalesCase::class, ['record' => $case->id])
            ->fillForm([
                'source' => 'Kantor Cabang',
            ])
            ->call('save')
            ->assertHasNoFormErrors();

        $this->assertSame('Kantor Cabang', $case->fresh()->source);
    }

    public function test_filament_create_page_creates_case_with_new_consumer(): void
    {
        $user = $this->hqAdmin();
        $unit = $this->makeUnit(Branch::factory()->create());

        $this->actingAs($user);

        Livewire::test(CreateSalesCase::class)
            ->fillForm([
                'create_new_consumer' => true,
                'new_consumer_nik' => '3325010101990003',
                'new_consumer_name' => 'Sri Wahyuni',
                'new_consumer_phone' => '081234567890',
                'unit_id' => $unit->id,
                'financing_type' => FinancingType::KprSubsidi->value,
            ])
            ->call('create')
            ->assertHasNoFormErrors();

        $consumer = Consumer::query()->where('nik', '3325010101990003')->firstOrFail();

        $this->assertDatabaseHas('sales_cases', [
            'consumer_id' => $consumer->id,
            'unit_id' => $unit->id,
            'case_status' => SalesCaseStatus::Active->value,
        ]);
        $this->assertSame(UnitStatus::Booking->value, $unit->fresh()->status->value);
    }

    public function test_filament_create_page_accepts_existing_consumer(): void
    {
        $user = $this->hqAdmin();
        $consumer = Consumer::factory()->create();
        $unit = $this->makeUnit(Branch::factory()->create());

        $this->actingAs($user);

        Livewire::test(CreateSalesCase::class)
            ->fillForm([
                'consumer_id' => $consumer->id,
                'unit_id' => $unit->id,
                'financing_type' => FinancingType::Cash->value,
            ])
            ->call('create')
            ->assertHasNoFormErrors();

        $this->assertDatabaseHas('sales_cases', [
            'consumer_id' => $consumer->id,
            'unit_id' => $unit->id,
        ]);
    }

    public function test_branch_admin_create_form_rejects_other_branch_unit(): void
    {
        $ownBranch = Branch::factory()->create();
        $otherBranch = Branch::factory()->create();

        $this->actingAs($this->branchAdmin($ownBranch));

        Livewire::test(CreateSalesCase::class)
            ->fillForm([
                'consumer_id' => Consumer::factory()->create()->id,
                'unit_id' => $this->makeUnit($otherBranch)->id,
                'financing_type' => FinancingType::KprSubsidi->value,
            ])
            ->call('create')
            ->assertHasFormErrors(['unit_id']);
    }

    public function test_sales_case_list_renders_for_hq_admin(): void
    {
        $branch = Branch::factory()->create();
        $case = $this->createCase($this->hqAdmin(), $this->makeUnit($branch));

        $this->actingAs($this->hqAdmin());

        Livewire::test(ListSalesCases::class)
            ->assertCanSeeTableRecords([$case])
            ->assertSuccessful();
    }
}
