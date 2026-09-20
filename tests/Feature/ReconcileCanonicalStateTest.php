<?php

namespace Tests\Feature;

use App\DeveloperPpjbStatus;
use App\Models\Branch;
use App\Models\Project;
use App\Models\SalesCase;
use App\Models\Unit;
use App\SalesCaseStage;
use App\SalesCaseStatus;
use App\UnitStatus;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Artisan;
use Tests\TestCase;

class ReconcileCanonicalStateTest extends TestCase
{
    use RefreshDatabase;

    public function test_dry_run_reports_without_mutating_and_apply_is_idempotent(): void
    {
        $this->seed();
        $branch = Branch::factory()->create();
        $project = Project::factory()->for($branch)->create();
        $unit = Unit::factory()->for($project)->create(['status' => UnitStatus::Tersedia]);
        $case = SalesCase::factory()->forUnit($unit)->create(['current_stage' => SalesCaseStage::DataKonsumen]);

        $this->artisan('oasis:reconcile-canonical-state', ['--branch-id' => $branch->id])
            ->assertExitCode(0)
            ->expectsOutputToContain('DRY RUN');
        $this->assertSame(SalesCaseStage::DataKonsumen, $case->refresh()->current_stage);
        $this->assertSame(UnitStatus::Tersedia, $unit->fresh()->status);

        $this->artisan('oasis:reconcile-canonical-state', ['--branch-id' => $branch->id, '--apply' => true])->assertExitCode(0);
        $this->assertSame(SalesCaseStage::BiChecking, $case->refresh()->current_stage);
        $this->assertSame(UnitStatus::Booking, $unit->fresh()->status);

        $exitCode = Artisan::call('oasis:reconcile-canonical-state', ['--branch-id' => $branch->id, '--apply' => true, '--json' => true]);
        $this->assertSame(0, $exitCode);
        $payload = json_decode(Artisan::output(), true, flags: JSON_THROW_ON_ERROR);
        $this->assertSame(0, $payload['sales_cases']['changes_required']);
        $this->assertSame(0, $payload['sales_cases']['changes_applied']);
        $this->assertSame(0, $payload['units']['changes_required']);
        $this->assertSame(0, $payload['units']['changes_applied']);
    }

    public function test_branch_scope_and_json_contract(): void
    {
        $this->seed();
        $branch = Branch::factory()->create();
        $project = Project::factory()->for($branch)->create();
        Unit::factory()->for($project)->create(['status' => UnitStatus::Tersedia]);

        $this->artisan('oasis:reconcile-canonical-state')->assertExitCode(1);
        $this->artisan('oasis:reconcile-canonical-state', ['--branch-id' => 'missing'])->assertExitCode(1);
        $this->assertSame(0, Artisan::call('oasis:reconcile-canonical-state', ['--branch-id' => $branch->id, '--json' => true]));
        $payload = json_decode(Artisan::output(), true, flags: JSON_THROW_ON_ERROR);
        foreach (['mode', 'context', 'branch', 'sales_cases', 'units', 'stage_transitions', 'unit_status_transitions', 'anomalies'] as $key) {
            $this->assertArrayHasKey($key, $payload);
        }
        foreach (['environment', 'database_connection', 'database_name', 'mode'] as $key) {
            $this->assertArrayHasKey($key, $payload['context']);
        }
    }

    public function test_apply_is_strictly_scoped_to_selected_branch(): void
    {
        $this->seed();
        $branchA = Branch::factory()->create();
        $branchB = Branch::factory()->create();
        $unitA = Unit::factory()->for(Project::factory()->for($branchA))->create(['status' => UnitStatus::Tersedia]);
        $unitB = Unit::factory()->for(Project::factory()->for($branchB))->create(['status' => UnitStatus::Tersedia]);
        $caseA = SalesCase::factory()->forUnit($unitA)->create(['current_stage' => SalesCaseStage::DataKonsumen]);
        $caseB = SalesCase::factory()->forUnit($unitB)->create(['current_stage' => SalesCaseStage::DataKonsumen]);

        $this->artisan('oasis:reconcile-canonical-state', ['--branch-id' => $branchA->id, '--apply' => true])->assertExitCode(0);

        $this->assertSame(SalesCaseStage::BiChecking, $caseA->refresh()->current_stage);
        $this->assertSame(UnitStatus::Booking, $unitA->fresh()->status);
        $this->assertSame(SalesCaseStage::DataKonsumen, $caseB->refresh()->current_stage);
        $this->assertSame(UnitStatus::Tersedia, $unitB->fresh()->status);
    }

    public function test_production_apply_requires_explicit_force(): void
    {
        $this->seed();
        $branch = Branch::factory()->create();
        $unit = Unit::factory()->for(Project::factory()->for($branch))->create(['status' => UnitStatus::Tersedia]);
        $case = SalesCase::factory()->forUnit($unit)->create(['current_stage' => SalesCaseStage::DataKonsumen]);
        $original = app()->environment();

        try {
            app()->detectEnvironment(fn (): string => 'production');
            $this->artisan('oasis:reconcile-canonical-state', ['--branch-id' => $branch->id, '--apply' => true])
                ->assertExitCode(1)
                ->expectsOutputToContain('requires --force-production');
            $this->assertSame(SalesCaseStage::DataKonsumen, $case->refresh()->current_stage);
            $this->assertSame(UnitStatus::Tersedia, $unit->fresh()->status);

            $this->artisan('oasis:reconcile-canonical-state', ['--branch-id' => $branch->id, '--apply' => true, '--force-production' => true])->assertExitCode(0);
            $this->assertSame(SalesCaseStage::BiChecking, $case->refresh()->current_stage);
            $this->assertSame(UnitStatus::Booking, $unit->fresh()->status);
        } finally {
            app()->detectEnvironment(fn (): string => $original);
        }
    }

    public function test_json_is_deterministic_pii_free_and_dry_run_reports_both_changes(): void
    {
        $this->seed();
        $branch = Branch::factory()->create();
        $unit = Unit::factory()->for(Project::factory()->for($branch))->create(['status' => UnitStatus::Tersedia]);
        $case = SalesCase::factory()->forUnit($unit)->create(['current_stage' => SalesCaseStage::DataKonsumen]);
        $case->consumer->update(['name' => 'SECRET_CONSUMER_NAME', 'nik' => '3374010101909999', 'phone' => 'SECRET_PHONE_VALUE']);

        $this->assertSame(0, Artisan::call('oasis:reconcile-canonical-state', ['--branch-id' => $branch->id, '--json' => true]));
        $firstRaw = Artisan::output();
        $first = json_decode($firstRaw, true, flags: JSON_THROW_ON_ERROR);
        $this->assertSame(1, $first['sales_cases']['changes_required']);
        $this->assertSame(0, $first['sales_cases']['changes_applied']);
        $this->assertSame(1, $first['units']['changes_required']);
        $this->assertSame(0, $first['units']['changes_applied']);
        $this->assertStringNotContainsString('SECRET_CONSUMER_NAME', $firstRaw);
        $this->assertStringNotContainsString('SECRET_PHONE_VALUE', $firstRaw);

        Artisan::call('oasis:reconcile-canonical-state', ['--branch-id' => $branch->id, '--json' => true]);
        $second = json_decode(Artisan::output(), true, flags: JSON_THROW_ON_ERROR);
        $this->assertSame($first['stage_transitions'], $second['stage_transitions']);
        $this->assertSame($first['unit_status_transitions'], $second['unit_status_transitions']);
        $this->assertSame($first['anomalies'], $second['anomalies']);
        $this->assertSame(SalesCaseStage::DataKonsumen, $case->refresh()->current_stage);
        $this->assertSame(UnitStatus::Tersedia, $unit->fresh()->status);
    }

    public function test_human_output_contains_audit_sections(): void
    {
        $this->seed();
        $branch = Branch::factory()->create();
        $command = $this->artisan('oasis:reconcile-canonical-state', ['--branch-id' => $branch->id]);
        foreach (['MODE:', 'ENVIRONMENT:', 'DATABASE:', 'BRANCH:', 'SALES CASES:', 'UNITS:', 'STAGE TRANSITIONS:', 'UNIT STATUS TRANSITIONS:', 'ANOMALY COUNTS:'] as $text) {
            $command->expectsOutputToContain($text);
        }
        $command->assertExitCode(0);
    }

    public function test_active_akad_stays_active_and_unit_becomes_sold(): void
    {
        $this->seed();
        $branch = Branch::factory()->create();
        $project = Project::factory()->for($branch)->create();
        $unit = Unit::factory()->for($project)->create(['status' => UnitStatus::Booking]);
        $case = SalesCase::factory()->forUnit($unit)->create(['current_stage' => SalesCaseStage::DataKonsumen]);
        $ppjb = $case->developerPpjbs()->create(['status' => DeveloperPpjbStatus::Active, 'document_date' => now()]);
        $case->akad()->create(['developer_ppjb_id' => $ppjb->id, 'akad_date' => now(), 'created_by' => $case->created_by]);

        $this->artisan('oasis:reconcile-canonical-state', ['--branch-id' => $branch->id, '--apply' => true])->assertExitCode(0);

        $this->assertSame(SalesCaseStage::Bast, $case->refresh()->current_stage);
        $this->assertSame(SalesCaseStatus::Active, $case->case_status);
        $this->assertSame(UnitStatus::Terjual, $unit->fresh()->status);
        $this->assertNull($case->closed_at);
        $this->assertSame(0, $case->bast()->count());
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
