<?php

namespace Tests\Feature;

use App\Actions\AdvanceCashCaseToPpjbAction;
use App\Actions\CreateAkadAction;
use App\Actions\CreateBastAction;
use App\Actions\CreateDeveloperPpjbAction;
use App\Actions\CreatePsjbAction;
use App\Actions\CreateSalesCaseAction;
use App\Actions\RecordBiCheckAction;
use App\BiCheckResult;
use App\Filament\Resources\AkadRecords\Pages\ListAkadRecords;
use App\Filament\Resources\BastRecords\Pages\ListBastRecords;
use App\Filament\Resources\DeveloperPpjbs\Pages\ListDeveloperPpjbs;
use App\FinancingType;
use App\Models\AkadRecord;
use App\Models\BastRecord;
use App\Models\Branch;
use App\Models\Consumer;
use App\Models\DeveloperPpjb;
use App\Models\Project;
use App\Models\Unit;
use App\Models\User;
use App\UserRole;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class PhaseFiveFilamentTest extends TestCase
{
    use RefreshDatabase;

    public function test_branch_lists_only_own_branch_records_and_read_only_roles_cannot_create(): void
    {
        $this->seed();
        $ownBranch = Branch::factory()->create();
        $otherBranch = Branch::factory()->create();
        $admin = User::factory()->for($ownBranch)->create();
        $admin->assignRole(UserRole::BranchAdmin);
        $hq = User::factory()->create();
        $hq->assignRole(UserRole::HqAdmin);
        [$ownPpjb, $ownAkad, $ownBast] = $this->records($hq, $ownBranch);
        [$otherPpjb, $otherAkad, $otherBast] = $this->records($hq, $otherBranch);

        $this->actingAs($admin);
        Livewire::test(ListDeveloperPpjbs::class)->assertCanSeeTableRecords([$ownPpjb])->assertCanNotSeeTableRecords([$otherPpjb]);
        Livewire::test(ListAkadRecords::class)->assertCanSeeTableRecords([$ownAkad])->assertCanNotSeeTableRecords([$otherAkad]);
        Livewire::test(ListBastRecords::class)->assertCanSeeTableRecords([$ownBast])->assertCanNotSeeTableRecords([$otherBast]);

        foreach ([UserRole::BranchManager, UserRole::Auditor] as $role) {
            $user = $role === UserRole::BranchManager ? User::factory()->for($ownBranch)->create() : User::factory()->create();
            $user->assignRole($role);
            $this->assertFalse($user->can('create', DeveloperPpjb::class));
            $this->assertFalse($user->can('create', AkadRecord::class));
            $this->assertFalse($user->can('create', BastRecord::class));
        }
    }

    /** @return array{DeveloperPpjb, AkadRecord, BastRecord} */
    private function records(User $hq, Branch $branch): array
    {
        $unit = Unit::factory()->for(Project::factory()->for($branch))->create();
        $case = app(CreateSalesCaseAction::class)->handle($hq, ['unit_id' => $unit->id, 'consumer_id' => Consumer::factory()->create()->id, 'financing_type' => FinancingType::Cash]);
        app(RecordBiCheckAction::class)->handle($hq, ['sales_case_id' => $case->id, 'check_date' => '2026-09-01', 'result' => BiCheckResult::Clear]);
        app(CreatePsjbAction::class)->handle($hq, ['sales_case_id' => $case->id, 'psjb_date' => '2026-09-02']);
        app(AdvanceCashCaseToPpjbAction::class)->handle($hq, $case);
        $ppjb = app(CreateDeveloperPpjbAction::class)->handle($hq, ['sales_case_id' => $case->id, 'document_date' => '2026-09-10']);
        $akad = app(CreateAkadAction::class)->handle($hq, ['sales_case_id' => $case->id, 'developer_ppjb_id' => $ppjb->id, 'akad_date' => '2026-09-20']);
        $bast = app(CreateBastAction::class)->handle($hq, ['sales_case_id' => $case->id, 'akad_id' => $akad->id, 'bast_date' => '2026-09-30']);

        return [$ppjb, $akad, $bast];
    }
}
