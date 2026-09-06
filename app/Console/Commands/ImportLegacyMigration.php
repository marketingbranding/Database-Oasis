<?php

namespace App\Console\Commands;

use App\Models\LegacyMigrationExecution;
use App\Models\LegacyMigrationPlan;
use App\Models\User;
use App\Services\LegacyMigrationBackupService;
use App\Services\LegacyMigrationImportService;
use App\Services\LegacyMigrationPreflightService;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;
use Illuminate\Database\QueryException;
use Throwable;

#[Signature('legacy:import {plan : Immutable migration plan ID} {--user-id= : Operator user ID}')]
#[Description('Run one controlled persistent legacy migration plan')]
class ImportLegacyMigration extends Command
{
    public function handle(
        LegacyMigrationPreflightService $preflight,
        LegacyMigrationBackupService $backups,
        LegacyMigrationImportService $imports,
    ): int {
        $plan = LegacyMigrationPlan::find($this->argument('plan'));
        if (! $plan instanceof LegacyMigrationPlan) {
            $this->error('Migration plan not found.');

            return self::FAILURE;
        }

        try {
            $summary = $preflight->verify($plan);
            $this->table(['Preflight', 'Value'], collect($summary)->map(fn ($value, $key): array => [$key, $value])->values()->all());

            if ($this->input->isInteractive() && ! $this->confirm("Import immutable plan {$plan->id}?", false)) {
                $this->warn('Import cancelled.');

                return self::FAILURE;
            }

            $actor = is_string($this->option('user-id')) ? User::find($this->option('user-id')) : null;
            $backupReference = $backups->create();
            $execution = LegacyMigrationExecution::create([
                'plan_id' => $plan->id,
                'plan_fingerprint' => $plan->plan_fingerprint,
                'status' => 'PENDING',
                'started_by' => $actor?->id,
                'environment' => app()->environment(),
                'database_connection' => (string) config('database.default'),
                'database_name' => (string) config('database.connections.'.config('database.default').'.database'),
                'backup_reference' => $backupReference,
                'backup_created_at' => now(),
                'preflight_summary' => $summary,
            ]);
            $execution->update(['status' => 'RUNNING', 'started_at' => now()]);

            try {
                $result = $imports->execute($plan, $execution);
            } catch (Throwable $exception) {
                $execution->update(['status' => 'FAILED', 'completed_at' => now(), 'failure_reason' => $exception->getMessage()]);
                throw $exception;
            }

            $execution->update(['status' => 'COMPLETED', 'completed_at' => now(), 'result_summary' => $result]);
            $this->info("Import completed: {$execution->id}");
            $this->line(json_encode($result, JSON_THROW_ON_ERROR));

            return self::SUCCESS;
        } catch (QueryException $exception) {
            $this->error($exception->getCode() === '23000' ? 'This immutable plan already has an execution.' : $exception->getMessage());

            return self::FAILURE;
        } catch (Throwable $exception) {
            $this->error($exception->getMessage());

            return self::FAILURE;
        }
    }
}
