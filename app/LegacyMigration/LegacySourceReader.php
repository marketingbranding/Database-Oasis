<?php

namespace App\LegacyMigration;

use DateInterval;
use DateTimeInterface;
use OpenSpout\Common\Entity\Cell;
use OpenSpout\Common\Entity\Cell\FormulaCell;
use OpenSpout\Reader\XLSX\Reader;
use RuntimeException;

class LegacySourceReader
{
    public function __construct(private readonly LegacyNormalizer $normalizer) {}

    /**
     * @return array<string, array{headers: array<int, string>, original_headers: array<int, string>, rows: array<int, array{row: int, values: array<string, mixed>, original: array<string, mixed>, formulas: array<int, string>}>}>
     */
    public function read(string $source): array
    {
        if (is_dir($source)) {
            return $this->readCsvDirectory($source);
        }

        if (! is_file($source) || ! is_readable($source)) {
            throw new RuntimeException("Legacy source tidak dapat dibaca: {$source}");
        }

        return match (strtolower(pathinfo($source, PATHINFO_EXTENSION))) {
            'csv' => [$this->sheetName($source) => $this->readCsv($source)],
            'xlsx' => $this->readXlsx($source),
            default => throw new RuntimeException('Format sumber harus direktori CSV, .csv, atau .xlsx.'),
        };
    }

    /** @return array<string, array{headers: array<int, string>, original_headers: array<int, string>, rows: array<int, array{row: int, values: array<string, mixed>, original: array<string, mixed>, formulas: array<int, string>}>}> */
    private function readCsvDirectory(string $source): array
    {
        $files = glob(rtrim($source, DIRECTORY_SEPARATOR).DIRECTORY_SEPARATOR.'*.csv') ?: [];
        sort($files, SORT_NATURAL | SORT_FLAG_CASE);

        if ($files === []) {
            throw new RuntimeException("Direktori tidak berisi file CSV: {$source}");
        }

        $sheets = [];
        foreach ($files as $file) {
            $sheets[$this->sheetName($file)] = $this->readCsv($file);
        }

        return $sheets;
    }

    /** @return array{headers: array<int, string>, original_headers: array<int, string>, rows: array<int, array{row: int, values: array<string, mixed>, original: array<string, mixed>, formulas: array<int, string>}>} */
    private function readCsv(string $source): array
    {
        $handle = fopen($source, 'rb');
        if ($handle === false) {
            throw new RuntimeException("CSV tidak dapat dibuka: {$source}");
        }

        try {
            $headerRow = fgetcsv($handle, escape: '');
            if ($headerRow === false) {
                throw new RuntimeException("CSV kosong: {$source}");
            }

            $originalHeaders = array_map(fn (mixed $value): string => trim((string) $value), $headerRow);
            $headers = array_map($this->normalizer->header(...), $originalHeaders);
            $rows = [];
            $rowNumber = 1;

            while (($values = fgetcsv($handle, escape: '')) !== false) {
                $rowNumber++;
                if ($this->emptyRow($values)) {
                    continue;
                }
                $rows[] = $this->makeRow($rowNumber, $headers, $originalHeaders, $values);
            }

            return ['headers' => $headers, 'original_headers' => $originalHeaders, 'rows' => $rows];
        } finally {
            fclose($handle);
        }
    }

    /** @return array<string, array{headers: array<int, string>, original_headers: array<int, string>, rows: array<int, array{row: int, values: array<string, mixed>, original: array<string, mixed>, formulas: array<int, string>}>}> */
    private function readXlsx(string $source): array
    {
        $reader = new Reader;
        $reader->open($source);
        $sheets = [];

        try {
            foreach ($reader->getSheetIterator() as $sheet) {
                $originalHeaders = [];
                $headers = [];
                $rows = [];
                $rowNumber = 0;

                foreach ($sheet->getRowIterator() as $row) {
                    $rowNumber++;
                    $cells = $row->getCells();
                    $values = array_map(fn ($cell): mixed => $this->scalar($cell), $cells);

                    if ($headers === []) {
                        $originalHeaders = array_map(fn (mixed $value): string => trim((string) $value), $values);
                        $headers = array_map($this->normalizer->header(...), $originalHeaders);

                        continue;
                    }

                    if ($this->emptyRow($values)) {
                        continue;
                    }

                    $formulas = [];
                    foreach ($cells as $index => $cell) {
                        if ($cell instanceof FormulaCell) {
                            $formulas[] = $headers[$index] ?? 'column_'.$index;
                        }
                    }

                    $rows[] = $this->makeRow($rowNumber, $headers, $originalHeaders, $values, $formulas);
                }

                $name = $this->normalizer->header($sheet->getName());
                $sheets[$name] = ['headers' => $headers, 'original_headers' => $originalHeaders, 'rows' => $rows];
            }
        } finally {
            $reader->close();
        }

        return $sheets;
    }

    /**
     * @param  array<int, string>  $headers
     * @param  array<int, string>  $originalHeaders
     * @param  array<int, mixed>  $values
     * @param  array<int, string>  $formulas
     * @return array{row: int, values: array<string, mixed>, original: array<string, mixed>, formulas: array<int, string>}
     */
    private function makeRow(int $rowNumber, array $headers, array $originalHeaders, array $values, array $formulas = []): array
    {
        $normalized = [];
        $original = [];
        foreach ($headers as $index => $header) {
            $normalized[$header] = $values[$index] ?? null;
            $original[$originalHeaders[$index] ?? $header] = $values[$index] ?? null;
            if (is_string($values[$index] ?? null) && str_starts_with((string) $values[$index], '=')) {
                $formulas[] = $header;
            }
        }

        return [
            'row' => $rowNumber,
            'values' => $normalized,
            'original' => $original,
            'formulas' => array_values(array_unique($formulas)),
        ];
    }

    /** @param array<int, mixed> $values */
    private function emptyRow(array $values): bool
    {
        return collect($values)->every(fn (mixed $value): bool => $value === null || trim((string) $value) === '');
    }

    private function sheetName(string $source): string
    {
        return $this->normalizer->header(pathinfo($source, PATHINFO_FILENAME));
    }

    private function scalar(mixed $cell): mixed
    {
        // The real Jepara workbook is a Google Sheets export: most identity
        // cells are formulas. Prefer the cached computed value; fall back to
        // the DUMMYFUNCTION fallback literal; never leak formula text as data.
        if ($cell instanceof FormulaCell) {
            $computed = $cell->getComputedValue();
            if ($computed !== null && ! (is_string($computed) && str_starts_with($computed, '#'))) {
                return $computed instanceof DateTimeInterface ? $computed->format('Y-m-d') : $computed;
            }

            return $this->dummyFunctionFallback($cell->getValue());
        }

        $value = $cell instanceof Cell ? $cell->getValue() : $cell;

        return match (true) {
            $value instanceof DateTimeInterface => $value->format('Y-m-d'),
            $value instanceof DateInterval => $value->format('%rP%yY%mM%dDT%hH%iM%sS'),
            is_bool($value), is_int($value), is_float($value), is_string($value), $value === null => $value,
            default => (string) $value,
        };
    }

    /**
     * Google Sheets exports wrap cached results as
     * `=IFERROR(__xludf.DUMMYFUNCTION(...),"cached-literal")`. Extract the
     * fallback literal; return null when nothing extractable exists.
     */
    private function dummyFunctionFallback(string $formula): mixed
    {
        if (preg_match('/__xludf\.DUMMYFUNCTION\(.*,\s*("(?:[^"\\\\]|\\\\.)*"|-?\d+(?:\.\d+)?(?:[eE][-+]?\d+)?)\s*\)\s*$/s', $formula, $matches) !== 1) {
            return null;
        }

        $fallback = $matches[1];

        if (str_starts_with($fallback, '"')) {
            $unescaped = stripcslashes(substr($fallback, 1, -1));

            return $unescaped === '' ? null : $unescaped;
        }

        return str_contains($fallback, '.') || str_contains(strtoupper($fallback), 'E')
            ? (float) $fallback
            : (int) $fallback;
    }
}
