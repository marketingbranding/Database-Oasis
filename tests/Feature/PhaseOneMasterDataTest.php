<?php

namespace Tests\Feature;

use App\Filament\Resources\Branches\Pages\ManageBranches;
use App\Filament\Resources\Projects\Pages\CreateProject;
use App\Filament\Resources\Projects\Pages\ListProjects;
use App\Filament\Resources\Units\Pages\CreateUnit;
use App\Filament\Resources\Units\Pages\ListUnits;
use App\Models\Bank;
use App\Models\Branch;
use App\Models\Project;
use App\Models\Unit;
use App\Models\User;
use App\ProjectStatus;
use App\UserRole;
use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Gate;
use Livewire\Livewire;
use Tests\TestCase;

class PhaseOneMasterDataTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed();
        Filament::setCurrentPanel(Filament::getPanel('admin'));
    }

    private function hqAdmin(): User
    {
        $user = User::factory()->create();
        $user->assignRole(UserRole::HqAdmin);

        return $user;
    }

    private function auditor(): User
    {
        $user = User::factory()->create();
        $user->assignRole(UserRole::Auditor);

        return $user;
    }

    private function branchAdmin(Branch $branch): User
    {
        $user = User::factory()->for($branch)->create();
        $user->assignRole(UserRole::BranchAdmin);

        return $user;
    }

    private function branchManager(Branch $branch): User
    {
        $user = User::factory()->for($branch)->create();
        $user->assignRole(UserRole::BranchManager);

        return $user;
    }

    public function test_master_data_models_receive_ulid_primary_keys(): void
    {
        foreach ([Branch::factory(), Project::factory(), Unit::factory(), Bank::factory()] as $factory) {
            $model = $factory->create();

            $this->assertMatchesRegularExpression('/^[0-9a-hjkmnp-tv-z]{26}$/', $model->id);
        }
    }

    public function test_master_data_relationships_resolve(): void
    {
        $branch = Branch::factory()->has(Project::factory()->has(Unit::factory()))->create();
        $user = User::factory()->for($branch)->create();

        $this->assertTrue($branch->projects->first()->branch->is($branch));
        $this->assertTrue($branch->projects->first()->units->first()->project->is($branch->projects->first()));
        $this->assertTrue($user->branch->is($branch));
    }

    public function test_database_seeder_creates_every_role(): void
    {
        foreach (UserRole::cases() as $role) {
            $this->assertDatabaseHas('roles', [
                'name' => $role->value,
                'guard_name' => 'web',
            ]);
        }
    }

    public function test_hq_admin_can_view_and_create_all_master_data(): void
    {
        $user = $this->hqAdmin();
        $branch = Branch::factory()->create();

        foreach ([Branch::class, Project::class, Unit::class, Bank::class, User::class] as $model) {
            $this->assertTrue(Gate::forUser($user)->allows('viewAny', $model));
            $this->assertTrue(Gate::forUser($user)->allows('create', $model));
        }

        $this->assertTrue(Gate::forUser($user)->allows('update', $branch));
        $this->assertTrue(Gate::forUser($user)->allows('delete', $branch));
    }

    public function test_auditor_can_view_but_not_mutate_master_data(): void
    {
        $user = $this->auditor();

        foreach ([Branch::class, Project::class, Unit::class, Bank::class, User::class] as $model) {
            $this->assertTrue(Gate::forUser($user)->allows('viewAny', $model));
            $this->assertFalse(Gate::forUser($user)->allows('create', $model));
        }

        $this->assertFalse(Gate::forUser($user)->allows('update', Branch::factory()->create()));
    }

    public function test_management_role_has_no_master_data_access(): void
    {
        $user = User::factory()->create();
        $user->assignRole(UserRole::Management);

        foreach ([Branch::class, Project::class, Unit::class, Bank::class, User::class] as $model) {
            $this->assertFalse(Gate::forUser($user)->allows('viewAny', $model));
        }
    }

    public function test_branch_admin_is_restricted_to_own_branch_records(): void
    {
        $ownBranch = Branch::factory()->create();
        $otherBranch = Branch::factory()->create();
        $user = $this->branchAdmin($ownBranch);

        $ownProject = Project::factory()->for($ownBranch)->create();
        $otherProject = Project::factory()->for($otherBranch)->create();

        $this->assertTrue(Gate::forUser($user)->allows('view', $ownProject));
        $this->assertTrue(Gate::forUser($user)->allows('update', $ownProject));
        $this->assertTrue(Gate::forUser($user)->allows('create', Project::class));
        $this->assertFalse(Gate::forUser($user)->allows('view', $otherProject));
        $this->assertFalse(Gate::forUser($user)->allows('update', $otherProject));
        $this->assertFalse(Gate::forUser($user)->allows('delete', $ownProject));

        $ownUnit = Unit::factory()->for($ownProject)->create();
        $otherUnit = Unit::factory()->for($otherProject)->create();

        $this->assertTrue(Gate::forUser($user)->allows('update', $ownUnit));
        $this->assertFalse(Gate::forUser($user)->allows('update', $otherUnit));
        $this->assertFalse(Gate::forUser($user)->allows('delete', $ownUnit));

        $this->assertTrue(Gate::forUser($user)->allows('view', $ownBranch));
        $this->assertFalse(Gate::forUser($user)->allows('view', $otherBranch));
        $this->assertFalse(Gate::forUser($user)->allows('update', $ownBranch));

        $this->assertTrue(Gate::forUser($user)->allows('viewAny', Bank::class));
        $this->assertFalse(Gate::forUser($user)->allows('update', Bank::factory()->create()));

        $ownBranchUser = User::factory()->for($ownBranch)->create();
        $otherBranchUser = User::factory()->for($otherBranch)->create();

        $this->assertTrue(Gate::forUser($user)->allows('view', $ownBranchUser));
        $this->assertFalse(Gate::forUser($user)->allows('view', $otherBranchUser));
        $this->assertFalse(Gate::forUser($user)->allows('update', $ownBranchUser));
    }

    public function test_branch_manager_is_read_only_within_own_branch(): void
    {
        $ownBranch = Branch::factory()->create();
        $user = $this->branchManager($ownBranch);

        $ownProject = Project::factory()->for($ownBranch)->create();

        $this->assertTrue(Gate::forUser($user)->allows('view', $ownProject));
        $this->assertFalse(Gate::forUser($user)->allows('update', $ownProject));
        $this->assertFalse(Gate::forUser($user)->allows('create', Project::class));
        $this->assertFalse(Gate::forUser($user)->allows('create', Unit::class));
    }

    public function test_hq_admin_can_create_project_through_filament_form(): void
    {
        $branch = Branch::factory()->create();

        $this->actingAs($this->hqAdmin());

        Livewire::test(CreateProject::class)
            ->fillForm([
                'branch_id' => $branch->id,
                'code' => 'PRJ-01',
                'name' => 'Perumahan Marison Jepara',
                'location' => 'Jepara',
            ])
            ->call('create')
            ->assertHasNoFormErrors();

        $this->assertDatabaseHas('projects', [
            'branch_id' => $branch->id,
            'code' => 'PRJ-01',
            'status' => ProjectStatus::Aktif->value,
        ]);
    }

    public function test_branch_admin_project_creation_is_forced_into_own_branch(): void
    {
        $ownBranch = Branch::factory()->create();
        $otherBranch = Branch::factory()->create();
        $user = $this->branchAdmin($ownBranch);

        $this->actingAs($user);

        Livewire::test(CreateProject::class)
            ->fillForm([
                'code' => 'PRJ-02',
                'name' => 'Perumahan Marison Mayong',
                'location' => 'Mayong',
            ])
            ->call('create')
            ->assertHasNoFormErrors();

        $this->assertDatabaseHas('projects', [
            'code' => 'PRJ-02',
            'branch_id' => $ownBranch->id,
        ]);
        $this->assertDatabaseMissing('projects', [
            'code' => 'PRJ-02',
            'branch_id' => $otherBranch->id,
        ]);
    }

    public function test_branch_admin_project_list_is_scoped_to_own_branch(): void
    {
        $ownBranch = Branch::factory()->create();
        $otherBranch = Branch::factory()->create();

        $ownProject = Project::factory()->for($ownBranch)->create();
        $otherProject = Project::factory()->for($otherBranch)->create();

        $this->actingAs($this->branchAdmin($ownBranch));

        Livewire::test(ListProjects::class)
            ->assertCanSeeTableRecords([$ownProject])
            ->assertCanNotSeeTableRecords([$otherProject]);
    }

    public function test_branch_admin_unit_list_is_scoped_to_own_branch(): void
    {
        $ownBranch = Branch::factory()->create();
        $otherBranch = Branch::factory()->create();

        $ownUnit = Unit::factory()->for(Project::factory()->for($ownBranch))->create();
        $otherUnit = Unit::factory()->for(Project::factory()->for($otherBranch))->create();

        $this->actingAs($this->branchAdmin($ownBranch));

        Livewire::test(ListUnits::class)
            ->assertCanSeeTableRecords([$ownUnit])
            ->assertCanNotSeeTableRecords([$otherUnit]);
    }

    public function test_branch_admin_cannot_open_edit_page_for_other_branch_unit(): void
    {
        $ownBranch = Branch::factory()->create();
        $otherBranch = Branch::factory()->create();

        $otherUnit = Unit::factory()->for(Project::factory()->for($otherBranch))->create();

        $this->actingAs($this->branchAdmin($ownBranch))
            ->get("/admin/units/{$otherUnit->id}/edit")
            ->assertNotFound();
    }

    public function test_branch_admin_cannot_create_unit_under_other_branch_project(): void
    {
        $ownBranch = Branch::factory()->create();
        $otherBranch = Branch::factory()->create();

        $otherProject = Project::factory()->for($otherBranch)->create();

        $this->actingAs($this->branchAdmin($ownBranch));

        Livewire::test(CreateUnit::class)
            ->fillForm([
                'project_id' => $otherProject->id,
                'unit_code' => 'K99',
            ])
            ->call('create')
            ->assertHasFormErrors(['project_id']);

        $this->assertDatabaseMissing('units', [
            'unit_code' => 'K99',
        ]);
    }

    public function test_hq_admin_can_create_branch_through_modal(): void
    {
        $this->actingAs($this->hqAdmin());

        Livewire::test(ManageBranches::class)
            ->callAction('create', data: [
                'code' => 'JBR',
                'name' => 'Jepara',
                'city' => 'Jepara',
                'province' => 'Jawa Tengah',
            ])
            ->assertHasNoFormErrors();

        $this->assertDatabaseHas('branches', [
            'code' => 'JBR',
            'name' => 'Jepara',
        ]);
    }

    public function test_branch_creation_requires_code(): void
    {
        $this->actingAs($this->hqAdmin());

        Livewire::test(ManageBranches::class)
            ->callAction('create', data: [
                'name' => 'Jepara',
                'city' => 'Jepara',
                'province' => 'Jawa Tengah',
            ])
            ->assertHasFormErrors(['code']);

        $this->assertDatabaseCount('branches', 0);
    }

    public function test_unit_code_must_be_unique_within_project(): void
    {
        $project = Project::factory()->create();
        Unit::factory()->for($project)->create(['unit_code' => 'K1']);

        $this->actingAs($this->hqAdmin());

        Livewire::test(CreateUnit::class)
            ->fillForm([
                'project_id' => $project->id,
                'unit_code' => 'K1',
            ])
            ->call('create')
            ->assertHasFormErrors(['unit_code']);
    }
}
