<?php

namespace Tests\Feature;

use App\Actions\CreateDocumentSubmissionAction;
use App\Actions\CreatePsjbAction;
use App\Actions\CreateSalesCaseAction;
use App\Actions\RecordBankResponseAction;
use App\Actions\RecordBiCheckAction;
use App\BankResponseType;
use App\BiCheckResult;
use App\Filament\Resources\BankProcesses\Pages\ListBankProcesses;
use App\Filament\Resources\DocumentSubmissions\Pages\ListDocumentSubmissions;
use App\FinancingType;
use App\Models\Bank;
use App\Models\BankProcess;
use App\Models\Branch;
use App\Models\Consumer;
use App\Models\DocumentSubmission;
use App\Models\Project;
use App\Models\Unit;
use App\Models\User;
use App\UserRole;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class PhaseFourFilamentTest extends TestCase
{
    use RefreshDatabase;

    public function test_branch_user_lists_only_own_branch_process_records(): void
    {
        $this->seed();
        $ownBranch = Branch::factory()->create();
        $otherBranch = Branch::factory()->create();
        $branchAdmin = User::factory()->for($ownBranch)->create();
        $branchAdmin->assignRole(UserRole::BranchAdmin);
        $hq = User::factory()->create();
        $hq->assignRole(UserRole::HqAdmin);

        [$ownSubmission, $ownProcess] = $this->records($hq, $ownBranch);
        [$otherSubmission, $otherProcess] = $this->records($hq, $otherBranch);

        $this->actingAs($branchAdmin);

        Livewire::test(ListDocumentSubmissions::class)
            ->assertCanSeeTableRecords([$ownSubmission])
            ->assertCanNotSeeTableRecords([$otherSubmission]);

        Livewire::test(ListBankProcesses::class)
            ->assertCanSeeTableRecords([$ownProcess])
            ->assertCanNotSeeTableRecords([$otherProcess]);
    }

    public function test_branch_manager_and_auditor_are_read_only(): void
    {
        $this->seed();
        $branch = Branch::factory()->create();
        $manager = User::factory()->for($branch)->create();
        $manager->assignRole(UserRole::BranchManager);
        $auditor = User::factory()->create();
        $auditor->assignRole(UserRole::Auditor);

        foreach ([$manager, $auditor] as $user) {
            $this->assertTrue($user->can('viewAny', DocumentSubmission::class));
            $this->assertTrue($user->can('viewAny', BankProcess::class));
            $this->assertFalse($user->can('create', DocumentSubmission::class));
            $this->assertFalse($user->can('create', BankProcess::class));
        }
    }

    /** @return array{DocumentSubmission, BankProcess} */
    private function records(User $hq, Branch $branch): array
    {
        $unit = Unit::factory()->for(Project::factory()->for($branch))->create();
        $case = app(CreateSalesCaseAction::class)->handle($hq, [
            'unit_id' => $unit->id, 'consumer_id' => Consumer::factory()->create()->id,
            'financing_type' => FinancingType::KprSubsidi,
        ]);
        app(RecordBiCheckAction::class)->handle($hq, [
            'sales_case_id' => $case->id, 'check_date' => '2026-09-01', 'result' => BiCheckResult::Clear,
        ]);
        app(CreatePsjbAction::class)->handle($hq, ['sales_case_id' => $case->id, 'psjb_date' => '2026-09-02']);
        $submission = app(CreateDocumentSubmissionAction::class)->handle($hq, [
            'sales_case_id' => $case->id, 'bank_id' => Bank::factory()->create()->id, 'submission_date' => '2026-09-10',
        ]);
        $process = app(RecordBankResponseAction::class)->handle($hq, [
            'sales_case_id' => $case->id, 'document_submission_id' => $submission->id,
            'bank_id' => $submission->bank_id, 'response_type' => BankResponseType::Process, 'response_date' => '2026-09-15',
        ]);

        return [$submission, $process];
    }
}
