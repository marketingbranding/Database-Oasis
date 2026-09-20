<?php

namespace Tests\Feature;

use App\BankResponseType;
use App\BastStatus;
use App\DeveloperPpjbStatus;
use App\FinancingType;
use App\Models\Bank;
use App\Models\BankProcess;
use App\Models\Branch;
use App\Models\DocumentSubmission;
use App\Models\Project;
use App\Models\SalesCase;
use App\Models\Unit;
use App\SalesCaseStage;
use App\SalesCaseStatus;
use App\UnitStatus;
use Illuminate\Database\UniqueConstraintViolationException;
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
        $this->assertStringNotContainsString('3374010101909999', $firstRaw);
        $this->assertStringNotContainsString('SECRET_PHONE_VALUE', $firstRaw);

        Artisan::call('oasis:reconcile-canonical-state', ['--branch-id' => $branch->id, '--json' => true]);
        $second = json_decode(Artisan::output(), true, flags: JSON_THROW_ON_ERROR);
        $this->assertSame($first['stage_transitions'], $second['stage_transitions']);
        $this->assertSame($first['unit_status_transitions'], $second['unit_status_transitions']);
        $this->assertSame($first['anomalies'], $second['anomalies']);
        $this->assertSame(SalesCaseStage::DataKonsumen, $case->refresh()->current_stage);
        $this->assertSame(UnitStatus::Tersedia, $unit->fresh()->status);
    }

    public function test_anomaly_json_reports_identifiers_deterministically_without_repairing_history(): void
    {
        $this->seed();
        $branch = Branch::factory()->create();
        $project = Project::factory()->for($branch)->create();
        $completed = SalesCase::factory()->create(['unit_id' => null, 'project_id' => $project->id, 'branch_id' => $branch->id, 'case_status' => SalesCaseStatus::Completed]);
        $finalized = SalesCase::factory()->create(['unit_id' => null, 'project_id' => $project->id, 'branch_id' => $branch->id]);
        $finalized->developerPpjbs()->create(['status' => DeveloperPpjbStatus::Active, 'document_date' => now()]);

        Artisan::call('oasis:reconcile-canonical-state', ['--branch-id' => $branch->id, '--json' => true]);
        $first = json_decode(Artisan::output(), true, flags: JSON_THROW_ON_ERROR);
        Artisan::call('oasis:reconcile-canonical-state', ['--branch-id' => $branch->id, '--json' => true]);
        $second = json_decode(Artisan::output(), true, flags: JSON_THROW_ON_ERROR);

        $this->assertGreaterThan(0, $first['anomalies']['total']);
        $this->assertArrayHasKey('completed_without_bast', $first['anomalies']['counts']);
        $this->assertArrayHasKey('unit_missing_at_finalization', $first['anomalies']['counts']);
        $this->assertContains($completed->id, array_column($first['anomalies']['items'], 'sales_case_id'));
        $this->assertContains($finalized->id, array_column($first['anomalies']['items'], 'sales_case_id'));
        $this->assertSame($first['anomalies'], $second['anomalies']);
        $this->assertSame(SalesCaseStatus::Completed, $completed->refresh()->case_status);
        $this->assertNull($finalized->refresh()->unit_id);
    }

    public function test_remaining_reachable_anomalies_are_reported_without_history_repair(): void
    {
        $this->seed();
        $branchA = Branch::factory()->create();
        $branchB = Branch::factory()->create();
        $projectA = Project::factory()->for($branchA)->create();
        $projectB = Project::factory()->for($branchB)->create();
        $unitB = Unit::factory()->for($projectB)->create();

        $mismatch = SalesCase::factory()->create(['branch_id' => $branchA->id, 'project_id' => $projectA->id, 'unit_id' => $unitB->id]);
        SalesCase::factory()->create(['branch_id' => $branchA->id, 'project_id' => $projectB->id, 'unit_id' => null]);
        $bastMismatch = SalesCase::factory()->create(['branch_id' => $branchA->id, 'case_status' => SalesCaseStatus::Active]);
        $ppjb = $bastMismatch->developerPpjbs()->create(['status' => DeveloperPpjbStatus::Active, 'document_date' => now()]);
        $akad = $bastMismatch->akad()->create(['developer_ppjb_id' => $ppjb->id, 'akad_date' => now()]);
        $bastMismatch->bast()->create(['akad_id' => $akad->id, 'bast_date' => now(), 'status' => BastStatus::Completed]);

        $akadWithoutPpjb = SalesCase::factory()->create(['branch_id' => $branchA->id]);
        $deletedPpjb = $akadWithoutPpjb->developerPpjbs()->create(['status' => DeveloperPpjbStatus::Active, 'document_date' => now()]);
        $akadWithoutPpjb->akad()->create(['developer_ppjb_id' => $deletedPpjb->id, 'akad_date' => now()]);
        $deletedPpjb->delete();

        $bankCase = SalesCase::factory()->create(['branch_id' => $branchA->id, 'financing_type' => FinancingType::KprSubsidi]);
        BankProcess::factory()->create(['sales_case_id' => $bankCase->id, 'is_authoritative' => true, 'sp3k_number' => null, 'sp3k_date' => null]);

        Artisan::call('oasis:reconcile-canonical-state', ['--branch-id' => $branchA->id, '--json' => true]);
        $payload = json_decode(Artisan::output(), true, flags: JSON_THROW_ON_ERROR);

        foreach (['project_unit_mismatch', 'branch_project_mismatch', 'unit_branch_mismatch', 'bast_status_mismatch', 'akad_without_ppjb', 'authoritative_sp3k_incomplete', 'bank_process_without_submission'] as $type) {
            $this->assertArrayHasKey($type, $payload['anomalies']['counts']);
        }
        $this->assertGreaterThanOrEqual(5, $payload['anomalies']['total']);
        $this->assertContains($mismatch->id, array_column($payload['anomalies']['items'], 'sales_case_id'));
        $this->assertSame(SalesCaseStatus::Active, $bastMismatch->refresh()->case_status);
        $this->assertSame($projectA->id, $mismatch->refresh()->project_id);
        $this->assertSame($unitB->id, $mismatch->unit_id);
        $this->assertNull($bankCase->bankProcesses()->firstOrFail()->sp3k_number);
    }

    public function test_database_guards_prevent_multiple_authoritative_bank_processes(): void
    {
        $this->seed();
        $case = SalesCase::factory()->create();
        BankProcess::factory()->approved()->create(['sales_case_id' => $case->id]);

        $this->expectException(UniqueConstraintViolationException::class);
        BankProcess::factory()->approved()->create(['sales_case_id' => $case->id]);
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

    public function test_apply_preserves_unrelated_sales_case_and_unit_fields(): void
    {
        $this->seed();
        $branch = Branch::factory()->create();
        $project = Project::factory()->for($branch)->create();
        $unit = Unit::factory()->for($project)->create(['status' => UnitStatus::Tersedia, 'unit_code' => 'SAFE-01', 'block' => 'A', 'number' => '01', 'building_progress' => 42]);
        $case = SalesCase::factory()->forUnit($unit)->create(['current_stage' => SalesCaseStage::DataKonsumen, 'source' => 'Sentinel', 'transfer_reason' => 'Keep', 'needs_review' => true, 'needs_review_reason' => 'Keep reason']);
        $caseBefore = $case->only(['consumer_id', 'unit_id', 'project_id', 'branch_id', 'financing_type', 'booking_date', 'source', 'transfer_reason', 'needs_review', 'needs_review_reason', 'sales_pic_id', 'coordinator_id', 'case_status']);
        $unitBefore = $unit->only(['project_id', 'unit_code', 'block', 'number', 'building_progress', 'electricity_status', 'water_status']);

        $this->artisan('oasis:reconcile-canonical-state', ['--branch-id' => $branch->id, '--apply' => true])->assertExitCode(0);

        $this->assertSame($caseBefore, $case->refresh()->only(array_keys($caseBefore)));
        $this->assertSame($unitBefore, $unit->refresh()->only(array_keys($unitBefore)));
        $this->assertSame(SalesCaseStage::BiChecking, $case->current_stage);
        $this->assertSame(UnitStatus::Booking, $unit->status);
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

    public function test_waiting_list_with_authoritative_sp3k_reconciles_stage_without_assigning_unit(): void
    {
        $this->seed();
        $branch = Branch::factory()->create();
        $project = Project::factory()->for($branch)->create();
        $case = SalesCase::factory()->create(['unit_id' => null, 'project_id' => $project->id, 'branch_id' => $branch->id, 'financing_type' => FinancingType::KprSubsidi, 'current_stage' => SalesCaseStage::DataKonsumen]);
        $bank = Bank::factory()->create();
        $submission = DocumentSubmission::factory()->create(['sales_case_id' => $case->id, 'bank_id' => $bank->id]);
        $process = BankProcess::factory()->create(['sales_case_id' => $case->id, 'document_submission_id' => $submission->id, 'bank_id' => $bank->id, 'response_type' => BankResponseType::Approved, 'is_authoritative' => true, 'sp3k_number' => 'SP3K-RECON', 'sp3k_date' => '2026-09-10']);
        $unitCount = Unit::query()->count();

        $this->artisan('oasis:reconcile-canonical-state', ['--branch-id' => $branch->id, '--apply' => true])->assertExitCode(0);

        $fresh = $case->refresh();
        $this->assertSame(SalesCaseStage::PpjbDev, $fresh->current_stage);
        $this->assertSame(SalesCaseStatus::Active, $fresh->case_status);
        $this->assertNull($fresh->unit_id);
        $this->assertSame($project->id, $fresh->project_id);
        $this->assertSame($unitCount, Unit::query()->count());
        $this->assertSame($submission->id, $submission->refresh()->id);
        $this->assertSame($process->id, $process->refresh()->id);
        $this->assertSame('SP3K-RECON', $process->sp3k_number);
        $this->assertSame('2026-09-10', $process->sp3k_date->toDateString());
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
