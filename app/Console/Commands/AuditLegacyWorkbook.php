<?php

namespace App\Console\Commands;

use App\LegacyMigration\JeparaLegacyAuditor;
use App\LegacyMigration\LegacyAuditReportWriter;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;
use RuntimeException;
use Throwable;

#[Signature('legacy:audit {branch : Pilot branch (jepara)} {source : CSV directory, CSV, or XLSX path} {--output= : Protected report directory}')]
#[Description('Audit and map an offline legacy workbook without importing domain records')]
class AuditLegacyWorkbook extends Command
{
    public function handle(JeparaLegacyAuditor $auditor, LegacyAuditReportWriter $writer): int
    {
        if (strtolower((string) $this->argument('branch')) !== 'jepara') {
            $this->error('Phase 8A hanya mendukung pilot Jepara.');

            return self::FAILURE;
        }

        $source = $this->resolvePath((string) $this->argument('source'));
        $output = $this->option('output');
        $outputPath = is_string($output) && $output !== ''
            ? $this->resolveOutputPath($output)
            : storage_path('app/private/legacy-audit/jepara');

        try {
            $report = $auditor->audit($source);
            $writer->write($report, $outputPath);
        } catch (Throwable $exception) {
            report_if(! $exception instanceof RuntimeException, $exception);
            $this->error($exception->getMessage());

            return self::FAILURE;
        }

        $summary = $report['summary'];
        $this->info('Legacy audit Jepara selesai (AUDIT ONLY — tidak ada import).');
        $this->table(['Metric', 'Count'], [
            ['Proposed Consumers', $summary['proposed_consumers']],
            ['Proposed Units', $summary['proposed_units']],
            ['Proposed Sales Cases', $summary['proposed_sales_cases']],
            ['KPR Cases', $summary['kpr_cases']],
            ['CASH Cases', $summary['cash_cases']],
            ['Ambiguous Mappings', $summary['ambiguous_mappings']],
            ['Unresolved Rows', $summary['unresolved_rows']],
            ['Chronology Violations', $summary['chronology_violations']],
            ['Orphan Records', $summary['orphan_records']],
        ]);
        $this->line("Report: {$outputPath}");

        return self::SUCCESS;
    }

    private function resolvePath(string $path): string
    {
        if (str_starts_with($path, '/') || preg_match('/^[A-Za-z]:[\\\\\/]/', $path) === 1) {
            return $path;
        }

        return base_path($path);
    }

    private function resolveOutputPath(string $path): string
    {
        if (str_starts_with($path, '/') || preg_match('/^[A-Za-z]:[\\\\\/]/', $path) === 1) {
            return $path;
        }

        return base_path($path);
    }
}
