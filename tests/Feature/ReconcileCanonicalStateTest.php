<?php

namespace Tests\Feature;

use App\Models\Branch;
use App\Models\Project;
use App\Models\SalesCase;
use App\Models\Unit;
use App\SalesCaseStage;
use App\UnitStatus;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ReconcileCanonicalStateTest extends TestCase
{
    use RefreshDatabase;

    public function test_dry_run_reports_without_mutating_and_apply_is_idempotent(): void
    {
        $this->seed();
        $branch = Branch::factory()->create();
        $project = Project::factory()->for($branch)->create();
        $unit = Unit::factory()->for($project)->create(['status' => UnitStatus::Booking]);
        $case = SalesCase::factory()->forUnit($unit)->create(['current_stage' => SalesCaseStage::DataKonsumen]);

        $this->artisan('oasis:reconcile-canonical-state', ['--branch-id' => $branch->id])
            ->assertExitCode(0)
            ->expectsOutputToContain('DRY RUN');
        $this->assertSame(SalesCaseStage::DataKonsumen, $case->refresh()->current_stage);
        $this->assertSame(UnitStatus::Booking, $unit->fresh()->status);

        $this->artisan('oasis:reconcile-canonical-state', ['--branch-id' => $branch->id, '--apply' => true])->assertExitCode(0);
        $this->assertSame(SalesCaseStage::BiChecking, $case->refresh()->current_stage);
        $this->assertSame(UnitStatus::Booking, $unit->fresh()->status);

        $this->artisan('oasis:reconcile-canonical-state', ['--branch-id' => $branch->id, '--apply' => true])->assertExitCode(0);
        $this->assertSame(SalesCaseStage::BiChecking, $case->refresh()->current_stage);
    }

    public function test_waiting_list_is_not_assigned_or_project_rewritten(): void
    {
        $this->seed();
        $branch = Branch::factory()->create();
        $project = Project::factory()->for($branch)->create();
        $case = SalesCase::factory()->create(['unit_id' => null, 'project_id' => $project->id, 'branch_id' => $branch->id]);

        $this->artisan('oasis:reconcile-canonical-state', ['--branch-id' => $branch->id, '--apply' => true])->assertExitCode(0);

        $fresh = $case->refresh();
        $this->assertNull($fresh->unit_id);
        $this->assertSame($project->id, $fresh->project_id);
    }
}
