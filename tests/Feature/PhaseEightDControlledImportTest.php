<?php

namespace Tests\Feature;

use App\MigrationReadiness;
use App\Models\Branch;
use App\Models\Consumer;
use App\Models\LegacyMigrationBatch;
use App\Models\LegacyMigrationCandidate;
use App\Models\LegacyMigrationExecution;
use App\Models\LegacyMigrationPlan;
use App\Models\LegacyMigrationProvenance;
use App\Models\Project;
use App\Models\SalesCase;
use App\Models\Unit;
use App\Models\User;
use App\Services\LegacyMigrationBackupService;
use App\Services\LegacyMigrationImportService;
use App\Services\LegacyMigrationPlanService;
use App\UserRole;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Artisan;
use Tests\TestCase;

class PhaseEightDControlledImportTest extends TestCase
{
    use RefreshDatabase;

    private User $user;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed();
        $this->user = User::factory()->create();
        $this->user->assignRole(UserRole::SuperAdmin);
        $backups = app(LegacyMigrationBackupService::class);
        $backups->useCheckpointCreator(fn (string $database): string => "test://{$database}/checkpoint.dump");
        app()->instance(LegacyMigrationBackupService::class, $backups);
    }

    public function test_preflight_rejects_changed_fingerprint_and_non_auto_candidate(): void
    {
        [$plan, $candidate] = $this->plan();
        $candidate->update(['readiness' => MigrationReadiness::Blocked]);

        $this->artisan('legacy:import', ['plan' => $plan->id, '--no-interaction' => true])
            ->expectsOutputToContain('fingerprint changed')
            ->assertFailed();
    }

    public function test_backup_failure_prevents_import(): void
    {
        [$plan] = $this->plan();
        app(LegacyMigrationBackupService::class)->useCheckpointCreator(fn (): never => throw new \RuntimeException('backup unavailable'));

        $this->artisan('legacy:import', ['plan' => $plan->id, '--no-interaction' => true])
            ->expectsOutputToContain('backup unavailable')
            ->assertFailed();

        $this->assertDatabaseCount('sales_cases', 0);
        $this->assertDatabaseCount('legacy_migration_executions', 0);
    }

    public function test_successful_import_completes_once_with_provenance_and_cash_isolation(): void
    {
        [$plan] = $this->plan();

        $exitCode = Artisan::call('legacy:import', ['plan' => $plan->id, '--user-id' => $this->user->id, '--no-interaction' => true]);
        $this->assertSame(0, $exitCode, Artisan::output());

        $this->assertDatabaseHas('legacy_migration_executions', ['plan_id' => $plan->id, 'status' => 'COMPLETED']);
        $this->assertDatabaseHas('sales_cases', ['financing_type' => 'CASH', 'is_legacy_import' => true]);
        $this->assertDatabaseCount('bank_processes', 0);
        $this->assertDatabaseHas('legacy_migration_provenances', ['plan_id' => $plan->id, 'source_row' => 2]);

        $this->artisan('legacy:import', ['plan' => $plan->id, '--no-interaction' => true])
            ->expectsOutputToContain('already completed')
            ->assertFailed();
        $this->assertDatabaseCount('sales_cases', 1);
    }

    public function test_operation_reconciliation_failure_rolls_back_and_records_failed_execution(): void
    {
        [$plan] = $this->plan();
        $imports = app(LegacyMigrationImportService::class);
        $imports->useFailureInjector(fn (string $gate) => $gate === 'operation_reconciliation' ? throw new \RuntimeException('operation mismatch') : null);
        app()->instance(LegacyMigrationImportService::class, $imports);

        $this->artisan('legacy:import', ['plan' => $plan->id, '--no-interaction' => true])->assertFailed();

        $this->assertDatabaseCount('sales_cases', 0);
        $this->assertDatabaseCount('legacy_migration_provenances', 0);
        $this->assertDatabaseHas('legacy_migration_executions', ['plan_id' => $plan->id, 'status' => 'FAILED', 'failure_reason' => 'operation mismatch']);
    }

    public function test_material_reconciliation_failure_rolls_back_and_records_failed_execution(): void
    {
        [$plan] = $this->plan();
        $imports = app(LegacyMigrationImportService::class);
        $imports->useFailureInjector(fn (string $gate) => $gate === 'material_reconciliation' ? throw new \RuntimeException('material mismatch') : null);
        app()->instance(LegacyMigrationImportService::class, $imports);

        $this->artisan('legacy:import', ['plan' => $plan->id, '--no-interaction' => true])->assertFailed();

        $this->assertDatabaseCount('sales_cases', 0);
        $this->assertDatabaseCount('legacy_migration_provenances', 0);
        $this->assertDatabaseHas('legacy_migration_executions', ['plan_id' => $plan->id, 'status' => 'FAILED', 'failure_reason' => 'material mismatch']);
    }

    public function test_existing_active_sales_case_aborts_before_domain_writes(): void
    {
        [$plan] = $this->plan();
        $branch = Branch::create(['code' => 'LEGACY-JEPARA', 'name' => 'Legacy Jepara', 'city' => 'Jepara', 'province' => 'Jawa Tengah']);
        $project = Project::create(['branch_id' => $branch->id, 'code' => 'MRG', 'name' => 'MRG', 'location' => 'Jepara', 'status' => 'AKTIF']);
        $unit = Unit::create(['project_id' => $project->id, 'unit_code' => 'A-01', 'block' => 'A', 'number' => '01', 'status' => 'BOOKING']);
        $consumer = Consumer::create(['nik' => '3201010101010001', 'name' => 'Cash User']);
        SalesCase::create(['consumer_id' => $consumer->id, 'unit_id' => $unit->id, 'project_id' => $project->id, 'branch_id' => $branch->id, 'financing_type' => 'CASH', 'current_stage' => 'DATA_KONSUMEN', 'case_status' => 'ACTIVE', 'created_by' => $this->user->id]);

        $this->artisan('legacy:import', ['plan' => $plan->id, '--no-interaction' => true])->assertFailed();

        $this->assertDatabaseCount('sales_cases', 1);
        $this->assertDatabaseCount('legacy_migration_executions', 0);
    }

    public function test_existing_plan_provenance_aborts_safely(): void
    {
        [$plan, $candidate] = $this->plan();
        LegacyMigrationProvenance::create(['plan_id' => $plan->id, 'batch_id' => $plan->batch_id, 'candidate_id' => $candidate->id, 'source_sheet' => 'data_konsumen', 'source_row' => 2, 'entity_type' => 'sales_case', 'source_values' => [], 'source_fingerprint' => 'source', 'audit_fingerprint' => 'audit']);

        $this->artisan('legacy:import', ['plan' => $plan->id, '--no-interaction' => true])->assertFailed();

        $this->assertDatabaseCount('sales_cases', 0);
        $this->assertDatabaseCount('legacy_migration_executions', 0);
    }

    public function test_unexpected_consumer_reuse_conflict_aborts_safely(): void
    {
        Consumer::create(['nik' => '3201010101010001', 'name' => 'Original Name']);
        [$plan] = $this->plan();
        Consumer::where('nik', '3201010101010001')->update(['name' => 'Changed Name']);

        $this->artisan('legacy:import', ['plan' => $plan->id, '--no-interaction' => true])->assertFailed();

        $this->assertDatabaseCount('sales_cases', 0);
        $this->assertDatabaseCount('legacy_migration_executions', 0);
    }

    public function test_completed_execution_rejects_second_import(): void
    {
        [$plan] = $this->plan();
        LegacyMigrationExecution::create(['plan_id' => $plan->id, 'plan_fingerprint' => $plan->plan_fingerprint, 'status' => 'COMPLETED', 'environment' => 'testing', 'database_connection' => 'sqlite', 'database_name' => ':memory:']);

        $this->artisan('legacy:import', ['plan' => $plan->id, '--no-interaction' => true])->assertFailed();

        $this->assertDatabaseCount('sales_cases', 0);
    }

    /** @return array{LegacyMigrationPlan, LegacyMigrationCandidate} */
    private function plan(): array
    {
        $batch = LegacyMigrationBatch::create([
            'source_filename' => 'protected.xlsx',
            'source_fingerprint' => 'source',
            'audit_fingerprint' => 'audit',
            'source_row_counts' => [],
            'status' => 'AUDITED',
            'created_by' => $this->user->id,
        ]);
        $candidate = LegacyMigrationCandidate::create([
            'batch_id' => $batch->id,
            'source_candidate_key' => 'cash-1',
            'proposed_consumer' => ['candidate_key' => 'nik:3201010101010001'],
            'proposed_unit' => ['candidate_key' => 'MRG|A-01'],
            'proposed_sales_case' => ['nik_normalized' => '3201010101010001', 'name_normalized' => 'Cash User', 'unit_key' => 'MRG|A-01', 'project_original' => 'MRG', 'unit_original' => 'A-01', 'financing' => 'CASH', 'lifecycle_status' => 'ACTIVE', 'process_rows' => ['data_konsumen' => [2]], 'proposed_history' => []],
            'proposed_history' => [],
            'confidence' => 'EXACT',
            'readiness' => MigrationReadiness::Auto,
            'lifecycle' => 'ACTIVE',
            'financing_type' => 'CASH',
            'source_evidence' => [],
            'source_fingerprint' => 'source',
        ]);
        $plan = app(LegacyMigrationPlanService::class)->generate($batch, $this->user);

        return [$plan, $candidate];
    }
}
