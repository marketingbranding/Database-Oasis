<?php

namespace App\LegacyMigration;

use RuntimeException;

class LegacyAuditReportWriter
{
    /** @param array<string, mixed> $report */
    public function write(array $report, string $directory): string
    {
        if (! is_dir($directory) && ! mkdir($directory, 0700, true) && ! is_dir($directory)) {
            throw new RuntimeException("Direktori report tidak dapat dibuat: {$directory}");
        }

        file_put_contents(
            $directory.DIRECTORY_SEPARATOR.'summary.json',
            json_encode($report, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR),
        );

        $this->writeCsv($directory.DIRECTORY_SEPARATOR.'consumers.csv', $report['consumers']);
        $this->writeCsv($directory.DIRECTORY_SEPARATOR.'units.csv', $report['units']);
        $this->writeCsv($directory.DIRECTORY_SEPARATOR.'sales_cases.csv', $report['sales_cases']);
        $this->writeCsv($directory.DIRECTORY_SEPARATOR.'document_mapping.csv', $report['document_mapping']);
        $this->writeCsv($directory.DIRECTORY_SEPARATOR.'exceptions.csv', $report['exceptions']);
        $this->writeCsv($directory.DIRECTORY_SEPARATOR.'duplicate_analysis.csv', $report['duplicate_analysis']);
        $this->writeCsv($directory.DIRECTORY_SEPARATOR.'chronology_issues.csv', $report['chronology_issues']);
        $this->writeCsv($directory.DIRECTORY_SEPARATOR.'unresolved_records.csv', $report['unresolved_records']);
        $this->writeCsv($directory.DIRECTORY_SEPARATOR.'schema_inventory.csv', $report['schema_inventory']);

        return $directory;
    }

    /** @param array<int, array<string, mixed>> $rows */
    private function writeCsv(string $path, array $rows): void
    {
        $handle = fopen($path, 'wb');
        if ($handle === false) {
            throw new RuntimeException("Report CSV tidak dapat dibuat: {$path}");
        }

        try {
            if ($rows === []) {
                return;
            }

            $headers = array_values(array_unique(array_merge(...array_map('array_keys', $rows))));
            fputcsv($handle, $headers, escape: '');

            foreach ($rows as $row) {
                fputcsv($handle, array_map(
                    fn (string $header): string|int|float|null => $this->scalar($row[$header] ?? null),
                    $headers,
                ), escape: '');
            }
        } finally {
            fclose($handle);
        }
    }

    private function scalar(mixed $value): string|int|float|null
    {
        return match (true) {
            $value === null, is_string($value), is_int($value), is_float($value) => $value,
            is_bool($value) => $value ? 'true' : 'false',
            default => json_encode($value, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR),
        };
    }
}
