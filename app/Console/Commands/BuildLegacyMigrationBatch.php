<?php

namespace App\Console\Commands;

use App\LegacyMigration\JeparaLegacyAuditor;
use App\LegacyMigration\LegacyAuditReportWriter;
use App\Models\User;
use App\Services\LegacyMigrationCandidateService;
use App\UserRole;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;
use RuntimeException;
use Throwable;

#[Signature('legacy:build-batch {branch : Pilot branch (jepara)} {source : XLSX/CSV path} {--output= : Protected audit report directory} {--user-id= : HQ/Super Admin user id}')]
#[Description('Build a Phase 8B migration candidate batch from a real audit')]
class BuildLegacyMigrationBatch extends Command
{
    public function handle(
        JeparaLegacyAuditor $auditor,
        LegacyAuditReportWriter $writer,
        LegacyMigrationCandidateService $candidates,
    ): int {
        if (strtolower((string) $this->argument('branch')) !== 'jepara') {
            $this->error('Phase 8B hanya mendukung pilot Jepara.');

            return self::FAILURE;
        }

        $source = $this->resolvePath((string) $this->argument('source'));
        $output = $this->option('output');
        $outputPath = is_string($output) && $output !== ''
            ? $this->resolvePath($output)
            : storage_path('app/private/legacy-audit/jepara');

        $userId = $this->option('user-id');
        $user = is_string($userId) && $userId !== ''
            ? User::find($userId)
            : User::query()->whereHas('roles', fn ($query) => $query->whereIn('name', [UserRole::HqAdmin->value, UserRole::SuperAdmin->value]))->first();

        if (! $user instanceof User) {
            $this->error('User HQ/Super Admin tidak ditemukan. Gunakan --user-id.');

            return self::FAILURE;
        }

        try {
            $report = $auditor->audit($source);
            $writer->write($report, $outputPath);
            $batch = $candidates->buildFromReport($outputPath, $user);

            $readiness = $batch->candidates()->get()->groupBy('readiness')->map->count();
            $this->info("Migration batch {$batch->id} dibuat dari {$report['summary']['proposed_sales_cases']} kandidat Sales Case (AUDIT ONLY).");
            $this->table(['Readiness', 'Count'], [
                ['AUTO', $readiness['AUTO'] ?? 0],
                ['REVIEW', $readiness['REVIEW'] ?? 0],
                ['BLOCKED', $readiness['BLOCKED'] ?? 0],
            ]);
        } catch (Throwable $exception) {
            report_if(! $exception instanceof RuntimeException, $exception);
            $this->error($exception->getMessage());

            return self::FAILURE;
        }

        return self::SUCCESS;
    }

    private function resolvePath(string $path): string
    {
        if (str_starts_with($path, '/') || preg_match('/^[A-Za-z]:[\\\\\/]/', $path) === 1) {
            return $path;
        }

        return base_path($path);
    }
}
