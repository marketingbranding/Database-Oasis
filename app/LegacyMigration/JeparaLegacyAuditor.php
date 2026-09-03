<?php

namespace App\LegacyMigration;

use Illuminate\Support\Arr;
use Illuminate\Support\Str;

class JeparaLegacyAuditor
{
    /** @var array<int, string> */
    public const TRANSACTION_SHEETS = ['data_konsumen', 'bi_checking', 'psjb', 'pemberkasan', 'proses_bank', 'ppjb_dev', 'akad', 'bast'];

    /** @var array<int, string> */
    public const RECONCILIATION_SHEETS = ['ringkasan_data', 'table_rekapan'];

    /** @var array<string, array<int, string>> */
    private const REQUIRED_COLUMNS = [
        'data_konsumen' => ['name', 'project', 'unit', 'financing'],
        'bi_checking' => ['result'],
        'psjb' => [],
        'pemberkasan' => [],
        'proses_bank' => ['result'],
        'ppjb_dev' => [],
        'akad' => [],
        'bast' => [],
    ];

    /** @var array<string, array<int, string>> */
    private const DATE_FIELDS = [
        'data_konsumen' => ['booking_date', 'date'],
        'bi_checking' => ['bi_date', 'date'],
        'psjb' => ['psjb_date', 'date'],
        'pemberkasan' => ['submission_date', 'date'],
        'proses_bank' => ['response_date', 'sp3k_date', 'date'],
        'ppjb_dev' => ['ppjb_date', 'date'],
        'akad' => ['akad_date', 'date'],
        'bast' => ['bast_date', 'date'],
    ];

    /** @var array<string, string> */
    private const DOCUMENT_FIELDS = [
        'psjb' => 'psjb_number',
        'proses_bank' => 'sp3k_number',
        'ppjb_dev' => 'ppjb_number',
        'akad' => 'akad_number',
        'bast' => 'bast_number',
    ];

    public function __construct(
        private readonly LegacySourceReader $reader,
        private readonly LegacyNormalizer $normalizer,
    ) {}

    /** @return array<string, mixed> */
    public function audit(string $source): array
    {
        $sheets = $this->reader->read($source);
        $exceptions = $this->emptyList();
        $duplicates = $this->emptyList();
        $chronology = $this->emptyList();
        $unresolved = $this->emptyList();
        $consumers = $this->emptyMap();
        $units = $this->emptyMap();
        $cases = $this->emptyList();
        $documents = $this->emptyList();

        $this->validateSheets($sheets, $exceptions);
        $schema = $this->schemaInventory($sheets);
        $this->buildCases($sheets['data_konsumen']['rows'] ?? [], $consumers, $units, $cases, $exceptions);
        $this->detectIdentityPatterns($cases, $exceptions);

        foreach (self::TRANSACTION_SHEETS as $sheet) {
            if ($sheet === 'data_konsumen') {
                continue;
            }

            foreach ($sheets[$sheet]['rows'] ?? [] as $row) {
                $caseIndexes = $this->resolveCaseIndexes($row['values'], $cases);
                if (count($caseIndexes) !== 1) {
                    $code = $this->orphanCode($sheet);
                    $this->exception($exceptions, $code, $sheet, $row['row'], count($caseIndexes) > 1 ? 'Lebih dari satu kandidat Sales Case.' : 'Sales Case tidak ditemukan.');
                    $unresolved[] = $this->trace($sheet, $row, ['reason' => count($caseIndexes) > 1 ? 'AMBIGUOUS' : 'UNRESOLVED']);

                    continue;
                }

                $caseIndex = $caseIndexes[0];
                $caseKey = $cases[$caseIndex]['candidate_key'];
                $this->auditProcessRow($sheet, $row, $caseKey, $cases[$caseIndex], $exceptions, $documents);
            }
        }

        $this->classifyDuplicates($sheets, $cases, $documents, $duplicates, $exceptions);
        $this->validateChronology($cases, $chronology, $exceptions);
        $reconciliation = $this->reconciliation($sheets, $cases);
        $confidence = collect($cases)->countBy('confidence')->sortKeys()->all();
        $exceptionCounts = collect($exceptions)->countBy('code')->sortKeys()->all();

        return [
            'meta' => [
                'branch' => 'Jepara',
                'source' => realpath($source) ?: $source,
                'audited_at' => now()->toIso8601String(),
                'mode' => 'AUDIT_ONLY',
                'normal_tables_written' => false,
            ],
            'summary' => [
                'legacy_rows_by_sheet' => collect($sheets)->map(fn (array $sheet): int => count($sheet['rows']))->all(),
                'proposed_consumers' => count($consumers),
                'proposed_units' => count($units),
                'proposed_sales_cases' => count($cases),
                'kpr_cases' => collect($cases)->where('financing', 'KPR_SUBSIDI')->count(),
                'cash_cases' => collect($cases)->where('financing', 'CASH')->count(),
                'completed_cases' => collect($cases)->where('lifecycle_status', 'COMPLETED')->count(),
                'active_cases' => collect($cases)->where('lifecycle_status', 'ACTIVE')->count(),
                'mundur_cases' => collect($cases)->where('lifecycle_status', 'MUNDUR')->count(),
                'reject_cases' => collect($cases)->where('lifecycle_status', 'REJECT')->count(),
                'pindah_kavling_candidates' => collect($exceptions)->where('code', AuditExceptionCode::PotentialPindahKavling->value)->count(),
                'multiple_bank_cases' => collect($duplicates)->where('classification', DuplicateClassification::MultipleBankAttempt->value)->count(),
                'duplicate_document_numbers' => collect($duplicates)->where('classification', DuplicateClassification::SameDocumentNumberDifferentCase->value)->count(),
                'chronology_violations' => count($chronology),
                'orphan_records' => collect($exceptions)->filter(fn (array $item): bool => str_starts_with($item['code'], 'ORPHAN_'))->count(),
                'ambiguous_mappings' => ($confidence[MappingConfidence::Ambiguous->value] ?? 0),
                'unresolved_rows' => count($unresolved),
            ],
            'schema_inventory' => $schema,
            'consumers' => array_values($consumers),
            'units' => array_values($units),
            'sales_cases' => array_values($cases),
            'document_mapping' => $documents,
            'exceptions' => $exceptions,
            'exception_counts' => $exceptionCounts,
            'confidence_distribution' => $confidence,
            'duplicate_analysis' => $duplicates,
            'chronology_issues' => $chronology,
            'unresolved_records' => $unresolved,
            'reconciliation' => $reconciliation,
        ];
    }

    /** @param array<string, mixed> $sheets
     * @param  array<int, array<string, mixed>>  $exceptions
     */
    private function validateSheets(array $sheets, array &$exceptions): void
    {
        foreach (self::TRANSACTION_SHEETS as $sheet) {
            if (! isset($sheets[$sheet])) {
                $this->exception($exceptions, AuditExceptionCode::MissingRequiredColumn, $sheet, 0, 'Sheet tidak ditemukan.');

                continue;
            }

            foreach (self::REQUIRED_COLUMNS[$sheet] as $column) {
                if (! in_array($column, $sheets[$sheet]['headers'], true)) {
                    $this->exception($exceptions, AuditExceptionCode::MissingRequiredColumn, $sheet, 1, "Kolom {$column} tidak ditemukan.");
                }
            }
        }
    }

    /** @param array<string, mixed> $sheets
     * @return array<int, array<string, mixed>>
     */
    private function schemaInventory(array $sheets): array
    {
        $inventory = [];
        foreach ($sheets as $sheetName => $sheet) {
            foreach ($sheet['headers'] as $column) {
                $sheetRows = $this->rowsFromSheet($sheet);
                $values = collect(array_map(fn (array $row): mixed => $row['values'][$column] ?? null, $sheetRows));
                $nonEmpty = $values->filter(fn (mixed $value): bool => $this->normalizer->text($value) !== null);
                $normalized = $nonEmpty->map($this->normalizer->comparisonText(...));
                $inventory[] = [
                    'sheet' => $sheetName,
                    'column' => $column,
                    'inferred_type' => $this->inferType($nonEmpty->all()),
                    'rows' => $values->count(),
                    'null_count' => $values->count() - $nonEmpty->count(),
                    'unique_count' => $normalized->unique()->count(),
                    'duplicate_count' => max(0, $nonEmpty->count() - $normalized->unique()->count()),
                    'examples' => $normalized->unique()->take(3)->values()->all(),
                    'candidate_identifier' => in_array($column, ['legacy_id', 'legacy_consumer_id', 'nik', 'project', 'unit'], true),
                    'foreign_reference_like' => in_array($column, ['legacy_consumer_id', 'nik', 'project', 'unit', 'bank'], true),
                    'formula_count' => collect($sheetRows)->filter(fn (array $row): bool => in_array($column, $row['formulas'], true))->count(),
                ];
            }
        }

        return $inventory;
    }

    /** @param array<int, array<string, mixed>> $rows
     * @param  array<string, array<string, mixed>>  $consumers
     * @param  array<string, array<string, mixed>>  $units
     * @param  array<int, array<string, mixed>>  $cases
     * @param  array<int, array<string, mixed>>  $exceptions
     */
    private function buildCases(array $rows, array &$consumers, array &$units, array &$cases, array &$exceptions): void
    {
        $nameGroups = collect($rows)->groupBy(fn (array $row): string => $this->normalizer->comparisonText($row['values']['name'] ?? null) ?? '');

        foreach ($rows as $row) {
            $values = $row['values'];
            $nik = $this->normalizer->nik($values['nik'] ?? null);
            $name = $this->normalizer->text($values['name'] ?? null);
            $normalizedName = $this->normalizer->comparisonText($name);
            $phone = $this->normalizer->phone($values['phone'] ?? null);
            $unitKey = $this->normalizer->unit($values['project'] ?? null, $values['block'] ?? null, $values['unit'] ?? null);
            $legacyLink = $this->normalizer->text($values['legacy_consumer_id'] ?? $values['legacy_id'] ?? null);

            if ($nik['empty']) {
                $this->exception($exceptions, AuditExceptionCode::ConsumerNikMissing, 'data_konsumen', $row['row'], 'NIK kosong; nama tidak boleh menjadi identitas tunggal.');
            } elseif (! $nik['valid']) {
                $this->exception($exceptions, AuditExceptionCode::ConsumerNikInvalid, 'data_konsumen', $row['row'], 'NIK bukan 16 digit setelah normalisasi aman.');
            }

            $sameName = $normalizedName === null ? collect() : $nameGroups->get($normalizedName, collect());
            $ambiguousName = ! $nik['valid'] && $sameName->count() > 1;
            if ($ambiguousName) {
                $this->exception($exceptions, AuditExceptionCode::ConsumerIdentityAmbiguous, 'data_konsumen', $row['row'], 'Nama sama tanpa NIK valid; tidak digabung otomatis.');
            }

            $consumerKey = $nik['valid']
                ? 'nik:'.$nik['value']
                : ($legacyLink !== null ? 'legacy:'.$legacyLink : 'row:data_konsumen:'.$row['row']);
            $confidence = $nik['valid']
                ? MappingConfidence::Exact
                : ($ambiguousName ? MappingConfidence::Ambiguous : ($legacyLink !== null ? MappingConfidence::High : MappingConfidence::Medium));

            $consumers[$consumerKey] ??= [
                'candidate_key' => $consumerKey,
                'legacy_rows' => [],
                'nik_masked' => $this->mask($nik['value']),
                'name_original' => $name,
                'name_normalized' => $normalizedName,
                'phone_masked' => $this->mask($phone),
                'confidence' => $confidence->value,
                'evidence' => $nik['valid'] ? ['VALID_EXACT_NIK'] : ($legacyLink !== null ? ['EXPLICIT_LEGACY_LINK'] : ['NAME_PHONE_CONTEXT_ONLY']),
            ];
            $consumers[$consumerKey]['legacy_rows'][] = $row['row'];

            if ($unitKey === '') {
                $this->exception($exceptions, AuditExceptionCode::UnitNotFound, 'data_konsumen', $row['row'], 'Project/unit kosong.');
            }
            $units[$unitKey] ??= [
                'candidate_key' => $unitKey,
                'project_original' => $this->normalizer->text($values['project'] ?? null),
                'unit_original' => $this->normalizer->text($values['unit'] ?? null),
                'normalized_key' => $unitKey,
                'confidence' => $unitKey === '' ? MappingConfidence::Unresolved->value : MappingConfidence::High->value,
                'legacy_rows' => [],
            ];
            $units[$unitKey]['legacy_rows'][] = $row['row'];

            $financing = $this->financing($values);
            $status = $this->lifecycleStatus($values);
            $booking = $this->firstDate($values, ['booking_date', 'date'], 'data_konsumen', $row['row'], $exceptions);
            $caseKey = hash('sha256', $consumerKey.'|'.$unitKey.'|'.$row['row']);
            $cases[] = [
                'candidate_key' => $caseKey,
                'consumer_key' => $consumerKey,
                'unit_key' => $unitKey,
                'legacy_consumer_id' => $legacyLink,
                'nik_normalized' => $nik['valid'] ? $nik['value'] : null,
                'name_normalized' => $normalizedName,
                'phone_normalized' => $phone,
                'financing' => $financing,
                'lifecycle_status' => $status,
                'previous_case_candidate' => null,
                'confidence' => $unitKey === '' ? MappingConfidence::Unresolved->value : $confidence->value,
                'evidence' => [$consumerKey, $unitKey, 'data_konsumen:'.$row['row']],
                'dates' => array_filter(['consumer' => $booking]),
                'process_rows' => ['data_konsumen' => [$row['row']]],
            ];
        }
    }

    /** @param array<int, array<string, mixed>> $cases
     * @param-out array<int, array<string, mixed>> $cases
     *
     * @param  array<int, array<string, mixed>>  $exceptions
     */
    private function detectIdentityPatterns(array &$cases, array &$exceptions): void
    {
        $consumerGroups = [];
        $unitGroups = [];
        foreach ($cases as $index => $case) {
            $consumerGroups[$case['consumer_key']][] = $index;
            $unitGroups[$case['unit_key']][] = $index;
        }

        foreach ($consumerGroups as $indexes) {
            if (count($indexes) < 2) {
                continue;
            }
            for ($position = 1; $position < count($indexes); $position++) {
                $previousIndex = $indexes[$position - 1];
                $currentIndex = $indexes[$position];
                if ($cases[$previousIndex]['unit_key'] !== $cases[$currentIndex]['unit_key']) {
                    $cases[$currentIndex]['previous_case_candidate'] = $cases[$previousIndex]['candidate_key'];
                    $this->exception($exceptions, AuditExceptionCode::PotentialPindahKavling, 'data_konsumen', $cases[$currentIndex]['process_rows']['data_konsumen'][0], 'Konsumen sama memiliki unit berbeda; perlu konfirmasi kelanjutan kronologis.');
                }
            }
        }

        foreach ($unitGroups as $indexes) {
            $activeCount = 0;
            foreach ($indexes as $index) {
                if ($cases[$index]['lifecycle_status'] === 'ACTIVE') {
                    $activeCount++;
                }
            }
            if ($activeCount > 1) {
                foreach ($indexes as $index) {
                    if ($cases[$index]['lifecycle_status'] === 'ACTIVE') {
                        $this->exception($exceptions, AuditExceptionCode::MultipleActiveUnitCandidates, 'data_konsumen', $cases[$index]['process_rows']['data_konsumen'][0], 'Satu unit memiliki beberapa kandidat transaksi aktif.');
                    }
                }
            }
        }
    }

    /** @param array<string, mixed> $values
     * @param  array<int, array<string, mixed>>  $cases
     * @return array<int, int>
     */
    private function resolveCaseIndexes(array $values, array $cases): array
    {
        $legacyLink = $this->normalizer->text($values['legacy_consumer_id'] ?? $values['legacy_id'] ?? null);
        $nik = $this->normalizer->nik($values['nik'] ?? null);
        $unitKey = $this->normalizer->unit($values['project'] ?? null, $values['block'] ?? null, $values['unit'] ?? null);
        $name = $this->normalizer->comparisonText($values['name'] ?? null);
        $phone = $this->normalizer->phone($values['phone'] ?? null);

        return collect($cases)->filter(function (array $case) use ($legacyLink, $nik, $unitKey, $name, $phone): bool {
            $linked = $legacyLink !== null && $case['legacy_consumer_id'] === $legacyLink;
            $exactNik = $nik['valid'] && $case['nik_normalized'] === $nik['value'];
            $composite = $name !== null && $phone !== null && $case['name_normalized'] === $name && $case['phone_normalized'] === $phone;
            $sameUnit = $unitKey === '' || $case['unit_key'] === $unitKey;

            return ($linked || $exactNik || $composite) && $sameUnit;
        })->keys()->all();
    }

    /** @param array<string, mixed> $row
     * @param  array<string, mixed>  $case
     * @param  array<int, array<string, mixed>>  $exceptions
     * @param  array<int, array<string, mixed>>  $documents
     */
    private function auditProcessRow(string $sheet, array $row, string $caseKey, array &$case, array &$exceptions, array &$documents): void
    {
        $values = $row['values'];
        $case['process_rows'][$sheet][] = $row['row'];
        foreach (self::DATE_FIELDS[$sheet] ?? [] as $field) {
            if (array_key_exists($field, $values)) {
                $date = $this->firstDate($values, [$field], $sheet, $row['row'], $exceptions);
                if ($date !== null) {
                    $case['dates'][$sheet.':'.$row['row'].':'.$field] = $date;
                }
            }
        }

        if ($sheet === 'proses_bank') {
            $result = Str::upper($this->normalizer->text($values['result'] ?? $values['status'] ?? null) ?? '');
            if (! in_array($result, ['PROCESS', 'REVISION', 'APPROVED', 'REJECTED'], true)) {
                $this->exception($exceptions, AuditExceptionCode::UnknownStatusValue, $sheet, $row['row'], "Status bank tidak dikenal: {$result}");
            }
            $sp3k = Str::upper($this->normalizer->document($values['sp3k_number'] ?? null) ?? '');
            if ($sp3k === 'CASH') {
                $this->exception($exceptions, AuditExceptionCode::CashFakeSp3k, $sheet, $row['row'], 'Literal CASH adalah placeholder, bukan SP3K.');
            }
        }

        if ($sheet === 'bi_checking') {
            $result = Str::upper($this->normalizer->text($values['result'] ?? $values['status'] ?? null) ?? '');
            if (! in_array($result, ['CLEAR', 'REVIEW', 'REJECTED'], true)) {
                $this->exception($exceptions, AuditExceptionCode::UnknownStatusValue, $sheet, $row['row'], "Hasil BI tidak dikenal: {$result}");
            }
        }

        if ($sheet === 'ppjb_dev' && ($case['financing'] === 'KPR_SUBSIDI') && empty($case['process_rows']['proses_bank'])) {
            $this->exception($exceptions, AuditExceptionCode::PpjbWithoutUpstream, $sheet, $row['row'], 'PPJB KPR tidak memiliki upstream Bank Process.');
        }
        if ($sheet === 'akad' && ! isset($case['process_rows']['ppjb_dev'])) {
            $this->exception($exceptions, AuditExceptionCode::PpjbWithoutUpstream, $sheet, $row['row'], 'Akad tidak memiliki PPJB Developer kandidat.');
        }
        if ($sheet === 'bast' && ! isset($case['process_rows']['akad'])) {
            $this->exception($exceptions, AuditExceptionCode::BastWithoutAkad, $sheet, $row['row'], 'BAST tidak memiliki Akad kandidat.');
        }

        $documentField = self::DOCUMENT_FIELDS[$sheet] ?? null;
        if ($documentField !== null && ($number = $this->normalizer->document($values[$documentField] ?? null)) !== null) {
            $documents[] = [
                'sheet' => $sheet,
                'row' => $row['row'],
                'sales_case_candidate' => $caseKey,
                'document_type' => strtoupper($documentField),
                'document_number' => $number,
                'identity_key' => $caseKey,
            ];
        }
    }

    /** @param array<string, mixed> $sheets
     * @param  array<int, array<string, mixed>>  $cases
     * @param  array<int, array<string, mixed>>  $documents
     * @param  array<int, array<string, mixed>>  $duplicates
     * @param  array<int, array<string, mixed>>  $exceptions
     */
    private function classifyDuplicates(array $sheets, array $cases, array $documents, array &$duplicates, array &$exceptions): void
    {
        $documentDuplicates = $this->emptyMap();

        foreach ($documents as $document) {
            $matches = collect($documents)->where('document_type', $document['document_type'])->where('document_number', $document['document_number']);
            $caseCount = $matches->pluck('sales_case_candidate')->unique()->count();
            if ($caseCount > 1) {
                $documentDuplicates[$document['document_type'].'|'.$document['document_number']] ??= [
                    'classification' => DuplicateClassification::SameDocumentNumberDifferentCase->value,
                    'document_type' => $document['document_type'],
                    'document_number' => $document['document_number'],
                    'sales_case_candidates' => $matches->pluck('sales_case_candidate')->unique()->values()->all(),
                ];
                $this->exception($exceptions, AuditExceptionCode::DuplicateDocumentNumber, $document['sheet'], $document['row'], 'Nomor sama pada Sales Case berbeda; record tetap independen.');
            }
        }

        foreach ($cases as $case) {
            $submissions = collect($this->rowsFromSheet($sheets['pemberkasan'] ?? []))->filter(fn (array $row): bool => in_array($row['row'], $case['process_rows']['pemberkasan'] ?? [], true));
            if ($submissions->pluck('values.bank')->filter()->map($this->normalizer->comparisonText(...))->unique()->count() > 1) {
                $duplicates[] = [
                    'classification' => DuplicateClassification::MultipleBankAttempt->value,
                    'sales_case_candidate' => $case['candidate_key'],
                    'banks' => $submissions->pluck('values.bank')->filter()->values()->all(),
                ];
            }

            $psjbRows = $case['process_rows']['psjb'] ?? [];
            if (count($psjbRows) > 1) {
                $duplicates[] = [
                    'classification' => DuplicateClassification::Reissue->value,
                    'document_type' => 'PSJB',
                    'sales_case_candidate' => $case['candidate_key'],
                    'rows' => $psjbRows,
                ];
            }

            $akadRows = $case['process_rows']['akad'] ?? [];
            if (count($akadRows) > 1) {
                foreach ($akadRows as $row) {
                    $this->exception($exceptions, AuditExceptionCode::MultipleAkad, 'akad', $row, 'Lebih dari satu Akad pada kandidat Sales Case.');
                }
            }
        }

        foreach ($this->documentDuplicates($documentDuplicates) as $duplicate) {
            $duplicates[] = $duplicate;
        }

        foreach ($sheets as $sheetName => $sheet) {
            $sheetRows = $this->rowsFromSheet($sheet);
            $seen = [];
            foreach ($sheetRows as $row) {
                $fingerprint = hash('sha256', json_encode($row['values'], JSON_THROW_ON_ERROR));
                if (isset($seen[$fingerprint])) {
                    $duplicates[] = [
                        'classification' => DuplicateClassification::ExactRowDuplicate->value,
                        'sheet' => $sheetName,
                        'rows' => [$seen[$fingerprint], $row['row']],
                    ];
                    $this->exception($exceptions, AuditExceptionCode::ExactRowDuplicate, $sheetName, $row['row'], 'Isi row identik dengan row sebelumnya.');
                } else {
                    $seen[$fingerprint] = $row['row'];
                }
            }
        }

        $duplicates = array_values($duplicates);
    }

    /** @param array<string, array<string, mixed>> $documentDuplicates
     * @return array<int, array<string, mixed>>
     */
    private function documentDuplicates(array $documentDuplicates): array
    {
        return array_values($documentDuplicates);
    }

    /** @return array<string, array<string, mixed>> */
    private function emptyMap(): array
    {
        return [];
    }

    /** @return array<int, array<string, mixed>> */
    private function emptyList(): array
    {
        return [];
    }

    /** @param array<string, mixed> $sheet
     * @return array<int, array<string, mixed>>
     */
    private function rowsFromSheet(array $sheet): array
    {
        return $sheet['rows'] ?? $this->emptyList();
    }

    /** @param array<int, array<string, mixed>> $cases
     * @param  array<int, array<string, mixed>>  $chronology
     * @param  array<int, array<string, mixed>>  $exceptions
     */
    private function validateChronology(array $cases, array &$chronology, array &$exceptions): void
    {
        $order = ['consumer', 'bi_checking', 'psjb', 'pemberkasan', 'proses_bank', 'ppjb_dev', 'akad', 'bast'];
        foreach ($cases as $case) {
            $previousDate = null;
            $previousStage = null;
            foreach ($order as $stage) {
                $dates = [];
                foreach ($case['dates'] as $key => $date) {
                    $matches = $stage === 'consumer' ? $key === 'consumer' : str_starts_with((string) $key, $stage.':');
                    if ($matches) {
                        $dates[] = $date;
                    }
                }
                sort($dates);
                foreach ($dates as $date) {
                    if ($previousDate !== null && $date < $previousDate) {
                        $issue = [
                            'sales_case_candidate' => $case['candidate_key'],
                            'previous_stage' => $previousStage,
                            'previous_date' => $previousDate,
                            'stage' => $stage,
                            'date' => $date,
                        ];
                        $chronology[] = $issue;
                        $this->exception($exceptions, AuditExceptionCode::ChronologyViolation, $stage, 0, json_encode($issue, JSON_THROW_ON_ERROR));
                    }
                    if ($previousDate === null || $date > $previousDate) {
                        $previousDate = $date;
                        $previousStage = $stage;
                    }
                }
            }
        }
    }

    /** @param array<string, mixed> $sheets
     * @param  array<int, array<string, mixed>>  $cases
     * @return array<string, array<string, int>>
     */
    private function reconciliation(array $sheets, array $cases): array
    {
        $legacy = [
            'akad' => count($this->rowsFromSheet($sheets['akad'] ?? [])),
            'bast' => count($this->rowsFromSheet($sheets['bast'] ?? [])),
            'sp3k' => collect($this->rowsFromSheet($sheets['proses_bank'] ?? []))->filter(fn (array $row): bool => filled($row['values']['sp3k_number'] ?? null) && Str::upper(trim((string) $row['values']['sp3k_number'])) !== 'CASH')->count(),
            'active_transactions' => collect($this->rowsFromSheet($sheets['data_konsumen'] ?? []))->filter(fn (array $row): bool => $this->lifecycleStatus($row['values']) === 'ACTIVE')->count(),
        ];
        $reconstructed = [
            'akad' => collect($cases)->filter(fn (array $case): bool => ! empty($case['process_rows']['akad']))->count(),
            'bast' => collect($cases)->filter(fn (array $case): bool => ! empty($case['process_rows']['bast']))->count(),
            'sp3k' => collect($this->rowsFromSheet($sheets['proses_bank'] ?? []))->filter(fn (array $row): bool => Str::upper((string) ($row['values']['result'] ?? '')) === 'APPROVED' && filled($row['values']['sp3k_number'] ?? null) && Str::upper(trim((string) $row['values']['sp3k_number'])) !== 'CASH')->count(),
            'active_transactions' => collect($cases)->where('lifecycle_status', 'ACTIVE')->count(),
        ];

        return [
            'legacy_secondary_baseline' => $legacy,
            'reconstructed_candidates' => $reconstructed,
            'differences' => collect($legacy)->map(fn (int $value, string $key): int => ($reconstructed[$key] ?? 0) - $value)->all(),
        ];
    }

    /** @param array<string, mixed> $values
     * @param  array<int, string>  $fields
     * @param  array<int, array<string, mixed>>  $exceptions
     */
    private function firstDate(array $values, array $fields, string $sheet, int $row, array &$exceptions): ?string
    {
        foreach ($fields as $field) {
            if (! array_key_exists($field, $values)) {
                continue;
            }
            $date = $this->normalizer->date($values[$field]);
            if (! $date['valid']) {
                $this->exception($exceptions, AuditExceptionCode::InvalidDate, $sheet, $row, "Tanggal {$field} invalid.");
            }
            if (! $date['empty']) {
                return $date['value'];
            }
        }

        return null;
    }

    /** @param array<string, mixed> $values */
    private function financing(array $values): string
    {
        $evidence = Str::upper($this->normalizer->text($values['financing'] ?? null) ?? '');
        if ($evidence === 'CASH' || Str::upper($this->normalizer->document($values['sp3k_number'] ?? null) ?? '') === 'CASH') {
            return 'CASH';
        }

        return 'KPR_SUBSIDI';
    }

    /** @param array<string, mixed> $values */
    private function lifecycleStatus(array $values): string
    {
        $value = Str::upper($this->normalizer->text($values['status'] ?? null) ?? 'ACTIVE');

        return match (true) {
            str_contains($value, 'PINDAH') => 'PINDAH_KAVLING',
            str_contains($value, 'MUNDUR') => 'MUNDUR',
            in_array($value, ['REJECT', 'REJECTED'], true) => 'REJECT',
            in_array($value, ['COMPLETE', 'COMPLETED', 'SELESAI'], true) => 'COMPLETED',
            default => 'ACTIVE',
        };
    }

    private function orphanCode(string $sheet): AuditExceptionCode
    {
        return match ($sheet) {
            'bi_checking' => AuditExceptionCode::OrphanBi,
            'psjb' => AuditExceptionCode::OrphanPsjb,
            'pemberkasan' => AuditExceptionCode::OrphanSubmission,
            'proses_bank' => AuditExceptionCode::OrphanBankProcess,
            default => AuditExceptionCode::SalesCaseAmbiguous,
        };
    }

    /** @param array<int, array<string, mixed>> $exceptions */
    private function exception(array &$exceptions, AuditExceptionCode $code, string $sheet, int $row, string $message): void
    {
        $exceptions[] = ['code' => $code->value, 'sheet' => $sheet, 'row' => $row, 'message' => $message];
    }

    /** @param array<string, mixed> $row
     * @param  array<string, mixed>  $extra
     * @return array<string, mixed>
     */
    private function trace(string $sheet, array $row, array $extra = []): array
    {
        return [
            'sheet' => $sheet,
            'row' => $row['row'],
            'legacy_id' => Arr::get($row, 'values.legacy_id'),
            ...$extra,
        ];
    }

    /** @param array<int, mixed> $values */
    private function inferType(array $values): string
    {
        if ($values === []) {
            return 'unknown';
        }
        if (collect($values)->every(fn (mixed $value): bool => is_numeric($value))) {
            return 'numeric';
        }
        if (collect($values)->every(fn (mixed $value): bool => $this->normalizer->date($value)['valid'])) {
            return 'date';
        }

        return 'string';
    }

    private function mask(?string $value): ?string
    {
        if ($value === null || $value === '') {
            return null;
        }

        return strlen($value) <= 4 ? str_repeat('*', strlen($value)) : substr($value, 0, 2).str_repeat('*', strlen($value) - 4).substr($value, -2);
    }
}
