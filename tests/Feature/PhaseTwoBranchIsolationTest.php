<?php

namespace Tests\Feature;

use App\Actions\CreateSalesCaseAction;
use App\Actions\MarkSalesCaseMundurAction;
use App\Filament\Resources\Consumers\Pages\ListConsumers;
use App\Filament\Resources\Projects\Pages\EditProject;
use App\Filament\Resources\SalesCases\Pages\ListSalesCases;
use App\Filament\Resources\Units\Pages\EditUnit;
use App\FinancingType;
use App\Models\Branch;
use App\Models\Consumer;
use App\Models\Project;
use App\Models\SalesCase;
use App\Models\Unit;
use App\Models\User;
use App\UserRole;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\ValidationException;
use Livewire\Livewire;
use Tests\TestCase;

class PhaseTwoBranchIsolationTest extends TestCase
{
    use RefreshDatabase;

    private Branch $ownBranch;

    private Branch $otherBranch;

    private User $branchAdmin;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed();

        $this->ownBranch = Branch::factory()->create();
        $this->otherBranch = Branch::factory()->create();
        $this->branchAdmin = User::factory()->for($this->ownBranch)->create();
        $this->branchAdmin->assignRole(UserRole::BranchAdmin);
    }

    private function makeUnit(Branch $branch): Unit
    {
        return Unit::factory()->for(Project::factory()->for($branch))->create();
    }

    private function activeCase(Branch $branch): SalesCase
    {
        $user = User::factory()->create();
        $user->assignRole(UserRole::HqAdmin);

        return app(CreateSalesCaseAction::class)->handle($user, [
            'unit_id' => $this->makeUnit($branch)->id,
            'financing_type' => FinancingType::KprSubsidi,
            'consumer_id' => Consumer::factory()->create()->id,
        ]);
    }

    public function test_branch_admin_access_matrix_for_sales_cases(): void
    {
        $ownCase = $this->activeCase($this->ownBranch);
        $otherCase = $this->activeCase($this->otherBranch);

        $this->assertTrue(Gate::forUser($this->branchAdmin)->allows('viewAny', SalesCase::class));
        $this->assertTrue(Gate::forUser($this->branchAdmin)->allows('view', $ownCase));
        $this->assertTrue(Gate::forUser($this->branchAdmin)->allows('update', $ownCase));
        $this->assertTrue(Gate::forUser($this->branchAdmin)->allows('create', SalesCase::class));
        $this->assertFalse(Gate::forUser($this->branchAdmin)->allows('view', $otherCase));
        $this->assertFalse(Gate::forUser($this->branchAdmin)->allows('update', $otherCase));
    }

    public function test_branch_manager_is_read_only_for_own_branch(): void
    {
        $manager = User::factory()->for($this->ownBranch)->create();
        $manager->assignRole(UserRole::BranchManager);

        $ownCase = $this->activeCase($this->ownBranch);

        $this->assertTrue(Gate::forUser($manager)->allows('viewAny', SalesCase::class));
        $this->assertTrue(Gate::forUser($manager)->allows('view', $ownCase));
        $this->assertFalse(Gate::forUser($manager)->allows('update', $ownCase));
        $this->assertFalse(Gate::forUser($manager)->allows('create', SalesCase::class));
    }

    public function test_auditor_is_read_only_for_all_branches(): void
    {
        $auditor = User::factory()->create();
        $auditor->assignRole(UserRole::Auditor);

        $ownCase = $this->activeCase($this->ownBranch);
        $otherCase = $this->activeCase($this->otherBranch);

        $this->assertTrue(Gate::forUser($auditor)->allows('view', $ownCase));
        $this->assertTrue(Gate::forUser($auditor)->allows('view', $otherCase));
        $this->assertFalse(Gate::forUser($auditor)->allows('update', $ownCase));
        $this->assertFalse(Gate::forUser($auditor)->allows('create', SalesCase::class));
    }

    public function test_hq_admin_has_full_access_to_all_cases(): void
    {
        $hq = User::factory()->create();
        $hq->assignRole(UserRole::HqAdmin);

        $otherCase = $this->activeCase($this->otherBranch);

        $this->assertTrue(Gate::forUser($hq)->allows('view', $otherCase));
        $this->assertTrue(Gate::forUser($hq)->allows('update', $otherCase));
        $this->assertTrue(Gate::forUser($hq)->allows('create', SalesCase::class));
    }

    public function test_branch_admin_cannot_open_cross_branch_case_pages(): void
    {
        $otherCase = $this->activeCase($this->otherBranch);

        $this->actingAs($this->branchAdmin)
            ->get("/admin/sales-cases/{$otherCase->id}")
            ->assertNotFound();

        $this->actingAs($this->branchAdmin)
            ->get("/admin/sales-cases/{$otherCase->id}/edit")
            ->assertNotFound();
    }

    public function test_branch_admin_cannot_forge_cross_branch_unit_on_create(): void
    {
        $otherUnit = $this->makeUnit($this->otherBranch);

        $this->expectException(ValidationException::class);

        app(CreateSalesCaseAction::class)->handle($this->branchAdmin, [
            'unit_id' => $otherUnit->id,
            'financing_type' => FinancingType::KprSubsidi,
            'consumer_id' => Consumer::factory()->create()->id,
        ]);
    }

    public function test_branch_admin_cannot_execute_actions_on_cross_branch_case(): void
    {
        $otherCase = $this->activeCase($this->otherBranch);

        $this->expectException(AuthorizationException::class);

        app(MarkSalesCaseMundurAction::class)->handle($this->branchAdmin, $otherCase, 'Tidak sah');
    }

    public function test_branch_admin_sales_case_list_is_scoped_to_own_branch(): void
    {
        $ownCase = $this->activeCase($this->ownBranch);
        $otherCase = $this->activeCase($this->otherBranch);

        $this->actingAs($this->branchAdmin);

        Livewire::test(ListSalesCases::class)
            ->assertCanSeeTableRecords([$ownCase])
            ->assertCanNotSeeTableRecords([$otherCase]);
    }

    public function test_branch_admin_consumer_list_is_scoped_to_own_branch(): void
    {
        $ownCase = $this->activeCase($this->ownBranch);
        $otherCase = $this->activeCase($this->otherBranch);

        $ownConsumer = $ownCase->consumer;
        $otherConsumer = $otherCase->consumer;

        $this->actingAs($this->branchAdmin);

        Livewire::test(ListConsumers::class)
            ->assertCanSeeTableRecords([$ownConsumer])
            ->assertCanNotSeeTableRecords([$otherConsumer]);
    }

    public function test_branch_admin_cannot_view_cross_branch_consumer(): void
    {
        $otherCase = $this->activeCase($this->otherBranch);

        $this->assertFalse(Gate::forUser($this->branchAdmin)->allows('view', $otherCase->consumer));
    }

    public function test_branch_admin_can_create_case_with_global_consumer_found_by_nik(): void
    {
        $consumer = Consumer::factory()->create();
        $this->activeCase($this->otherBranch);

        $ownUnit = $this->makeUnit($this->ownBranch);

        $case = app(CreateSalesCaseAction::class)->handle($this->branchAdmin, [
            'unit_id' => $ownUnit->id,
            'financing_type' => FinancingType::KprSubsidi,
            'consumer_id' => $consumer->id,
        ]);

        $this->assertSame($this->ownBranch->id, $case->branch_id);
        $this->assertSame($consumer->id, $case->consumer_id);
        $this->assertTrue(Gate::forUser($this->branchAdmin)->allows('view', $case->consumer));
    }

    public function test_unit_with_sales_case_history_cannot_be_reassigned_to_another_project(): void
    {
        $hq = User::factory()->create();
        $hq->assignRole(UserRole::HqAdmin);

        $case = $this->activeCase($this->ownBranch);
        $unit = $case->unit;
        $otherProject = Project::factory()->for($this->ownBranch)->create();

        $this->actingAs($hq);

        Livewire::test(EditUnit::class, ['record' => $unit->id])
            ->set('data.project_id', $otherProject->id)
            ->call('save');

        $this->assertSame($case->project_id, $unit->fresh()->project_id);
    }

    public function test_project_with_transactional_history_cannot_be_moved_to_another_branch(): void
    {
        $hq = User::factory()->create();
        $hq->assignRole(UserRole::HqAdmin);

        $case = $this->activeCase($this->ownBranch);
        $project = $case->project;
        $otherBranch = Branch::factory()->create();

        $this->actingAs($hq);

        Livewire::test(EditProject::class, ['record' => $project->id])
            ->set('data.branch_id', $otherBranch->id)
            ->call('save');

        $this->assertSame($this->ownBranch->id, $project->fresh()->branch_id);
    }
}
