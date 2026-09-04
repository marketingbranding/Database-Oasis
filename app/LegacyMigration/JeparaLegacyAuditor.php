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
        'data_konsumen' => ['name', 'nik', 'id_kavling', 'status_cash'],
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
        'pemberkasan' => 'submission_number',
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
                    $matches = array_map(fn (int $idx): array => [
                        'source_candidate_key' => $cases[$idx]['candidate_key'],
                        'consumer_key' => $cases[$idx]['consumer_key'],
                        'unit_key' => $cases[$idx]['unit_key'],
                        'confidence' => $cases[$idx]['confidence'],
                        'reason' => 'SUGGESTED_MATCH',
                    ], $caseIndexes);
                    $unresolved[] = $this->trace($sheet, $row, [
                        'reason' => count($caseIndexes) > 1 ? 'AMBIGUOUS' : 'UNRESOLVED',
                        'candidate_count' => count($caseIndexes),
                        'candidate_matches' => $matches,
                        ...$this->ambiguityDiagnostic($row['values'], $cases),
                    ]);

                    continue;
                }

                $caseIndex = $caseIndexes[0];
                $caseKey = $cases[$caseIndex]['candidate_key'];
                $this->auditProcessRow($sheet, $row, $caseKey, $cases[$caseIndex], $exceptions, $documents);
            }
        }

        $this->markAuthoritativeProsesBank($cases);
        $this->resolveFinancingFromDownstreamEvidence($cases, $exceptions);
        $this->classifyDuplicates($sheets, $cases, $documents, $duplicates, $exceptions);
        $this->validateChronology($cases, $chronology, $exceptions);
        $reconciliation = $this->reconciliation($sheets, $cases);
        $confidence = collect($cases)->countBy('confidence')->sortKeys()->all();
        $exceptionCounts = collect($exceptions)->countBy('code')->sortKeys()->all();

        $sourceFingerprint = is_file($source) ? hash_file('sha256', $source) : null;
        $auditFingerprint = hash('sha256', json_encode([
            'cases' => array_map(fn (array $case): array => Arr::only($case, ['candidate_key', 'consumer_key', 'unit_key', 'financing', 'lifecycle_status', 'confidence']), $cases),
            'exceptions' => array_map(fn (array $exception): array => Arr::only($exception, ['code', 'sheet', 'row', 'message']), $exceptions),
        ], JSON_THROW_ON_ERROR));

        return [
            'meta' => [
                'branch' => 'Jepara',
                'source' => realpath($source) ?: $source,
                'source_fingerprint' => $sourceFingerprint,
                'audit_fingerprint' => $auditFingerprint,
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
                'financing_unresolved_cases' => collect($cases)->where('financing', 'UNRESOLVED')->count(),
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
            'migration_analysis' => $this->migrationAnalysis($sheets, $cases, $exceptions, $duplicates, $unresolved),
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
            $idKavling = $this->normalizer->text($values['id_kavling'] ?? null);
            $unitKey = $idKavling !== null
                ? $this->normalizer->unitFromIdKavling($idKavling)
                : $this->normalizer->unit($values['project'] ?? null, $values['block'] ?? null, $values['unit'] ?? null);
            // The workbook's explicit process linkage is id_kons (legacy_id
            // after header normalization), not a stable consumer ID on the
            // data_konsumen base sheet. Do not use id_kavling as consumer ID.
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
            } elseif ($idKavling !== null && ! $this->normalizer->hasDeterministicUnitSuffix($idKavling)) {
                $this->exception($exceptions, AuditExceptionCode::UnitCodeAmbiguous, 'data_konsumen', $row['row'], "Batas project/unit tidak deterministik; raw dipertahankan: {$idKavling}");
            }
            $units[$unitKey] ??= [
                'candidate_key' => $unitKey,
                'project_original' => $idKavling !== null && $this->normalizer->hasDeterministicUnitSuffix($idKavling)
                    ? Str::beforeLast($idKavling, '-')
                    : $this->normalizer->text($values['project'] ?? null),
                'unit_original' => $idKavling !== null && $this->normalizer->hasDeterministicUnitSuffix($idKavling)
                    ? Str::afterLast($idKavling, '-')
                    : $this->normalizer->text($values['unit'] ?? null),
                'id_kavling_original' => $idKavling,
                'normalized_key' => $unitKey,
                'confidence' => match (true) {
                    $unitKey === '' => MappingConfidence::Unresolved->value,
                    str_starts_with($unitKey, 'RAW|') => MappingConfidence::Ambiguous->value,
                    default => MappingConfidence::High->value,
                },
                'legacy_rows' => [],
            ];
            $units[$unitKey]['legacy_rows'][] = $row['row'];

            $financing = $this->financing($values);
            if ($financing['exception'] !== null) {
                $this->exception($exceptions, $financing['exception'], 'data_konsumen', $row['row'], $financing['message']);
            }
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
                'financing' => $financing['value'],
                'financing_confidence' => $financing['confidence'],
                'financing_evidence' => $financing['evidence'],
                'lifecycle_status' => $status,
                'lifecycle_source' => Str::upper($this->normalizer->text($values['lifecycle_status'] ?? $values['status'] ?? null) ?? ''),
                'previous_case_candidate' => null,
                'confidence' => match (true) {
                    $unitKey === '' => MappingConfidence::Unresolved->value,
                    str_starts_with($unitKey, 'RAW|') => MappingConfidence::Ambiguous->value,
                    default => $confidence->value,
                },
                'evidence' => [$consumerKey, $unitKey, 'data_konsumen:'.$row['row']],
                'dates' => array_filter(['consumer' => $booking]),
                'process_rows' => ['data_konsumen' => [$row['row']]],
                'proposed_history' => [
                    'data_konsumen' => [
                        [
                            'source_sheet' => 'data_konsumen',
                            'source_row' => $row['row'],
                            'legacy_id' => $legacyLink,
                            'original_values' => $row['original'] ?? [],
                            'date_normalized' => $booking,
                        ],
                    ],
                    'bi_checking' => [],
                    'psjb' => [],
                    'pemberkasan' => [],
                    'proses_bank' => [],
                    'ppjb_dev' => [],
                    'akad' => [],
                    'bast' => [],
                ],
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
        $candidateNik = null;
        if (! $nik['valid'] && $legacyLink !== null && preg_match('/(\d{16})$/', $legacyLink, $match) === 1) {
            // Candidate evidence only. Never assigned to canonical NIK or used
            // to promote confidence; it may match an existing trusted NIK.
            $candidateNik = $match[1];
        }
        $idKavling = $this->normalizer->text($values['id_kavling'] ?? null);
        $unitKey = $idKavling !== null
            ? $this->normalizer->unitFromIdKavling($idKavling)
            : $this->normalizer->unit($values['project'] ?? null, $values['block'] ?? null, $values['unit'] ?? null);
        $name = $this->normalizer->comparisonText($values['name'] ?? null);
        $phone = $this->normalizer->phone($values['phone'] ?? null);

        return collect($cases)->filter(function (array $case) use ($legacyLink, $nik, $candidateNik, $unitKey, $name, $phone): bool {
            $linked = $legacyLink !== null && $case['legacy_consumer_id'] === $legacyLink;
            $exactNik = $nik['valid'] && $case['nik_normalized'] === $nik['value'];
            $corroboratedCandidateNik = $candidateNik !== null && $case['nik_normalized'] === $candidateNik;
            $sameName = $name !== null && $case['name_normalized'] === $name;
            $samePhone = $phone !== null && $case['phone_normalized'] === $phone;
            $sameUnit = $unitKey === '' || $case['unit_key'] === $unitKey;

            // Name + unit is candidate matching only. The caller accepts only a
            // single existing case on that exact unit; no Consumer is merged.
            return ($linked || $exactNik || $corroboratedCandidateNik || ($sameName && ($samePhone || $sameUnit))) && $sameUnit;
        })->keys()->all();
    }

    /**
     * @param  array<string, mixed>  $values
     * @param  array<int, array<string, mixed>>  $cases
     * @return array<string, bool|int|string|null>
     */
    private function ambiguityDiagnostic(array $values, array $cases): array
    {
        $legacyLink = $this->normalizer->text($values['legacy_consumer_id'] ?? $values['legacy_id'] ?? null);
        $nik = $this->normalizer->nik($values['nik'] ?? null);
        $candidateNik = null;
        if (! $nik['valid'] && $legacyLink !== null && preg_match('/(\d{16})$/', $legacyLink, $match) === 1) {
            $candidateNik = $match[1];
        }
        $idKavling = $this->normalizer->text($values['id_kavling'] ?? null);
        $unitKey = $idKavling !== null
            ? $this->normalizer->unitFromIdKavling($idKavling)
            : $this->normalizer->unit($values['project'] ?? null, $values['block'] ?? null, $values['unit'] ?? null);
        $name = $this->normalizer->comparisonText($values['name'] ?? null);
        $phone = $this->normalizer->phone($values['phone'] ?? null);

        $consumerMatches = collect($cases)->filter(function (array $case) use ($legacyLink, $nik, $candidateNik, $name, $phone): bool {
            return ($legacyLink !== null && $case['legacy_consumer_id'] === $legacyLink)
                || ($nik['valid'] && $case['nik_normalized'] === $nik['value'])
                || ($candidateNik !== null && $case['nik_normalized'] === $candidateNik)
                || ($name !== null && $case['name_normalized'] === $name && ($phone === null || $case['phone_normalized'] === $phone));
        })->count();

        $unitMatches = collect($cases)->filter(fn (array $case): bool => $unitKey !== '' && $case['unit_key'] === $unitKey)->count();
        $combinedMatches = collect($cases)->filter(fn (array $case): bool => $unitKey !== '' && $case['unit_key'] === $unitKey
            && (($legacyLink !== null && $case['legacy_consumer_id'] === $legacyLink)
                || ($nik['valid'] && $case['nik_normalized'] === $nik['value'])
                || ($candidateNik !== null && $case['nik_normalized'] === $candidateNik)
                || ($name !== null && $case['name_normalized'] === $name)))->count();

        $identityMissing = $legacyLink === null && ! $nik['valid'] && $name === null;
        $unitMissing = $unitKey === '';

        $reason = match (true) {
            $identityMissing => 'MISSING_CONSUMER_IDENTITY',
            $unitMissing => 'MISSING_UNIT_IDENTITY',
            $combinedMatches > 1 => 'NAME_AND_UNIT_MATCHED_MULTIPLE_HISTORICAL_CASES',
            $consumerMatches > 1 => 'MULTIPLE_CONSUMER_CANDIDATES_ON_UNIT',
            $consumerMatches === 0 && $unitMatches > 0 => 'UNIT_RESOLVED_BUT_CONSUMER_UNRESOLVED',
            default => 'OTHER',
        };

        return [
            'ambiguity_reason' => $reason,
            'consumer_candidate_count' => $consumerMatches,
            'unit_candidate_count' => $unitMatches,
            'combined_candidate_count' => $combinedMatches,
            'has_legacy_link' => $legacyLink !== null,
            'has_exact_nik' => $nik['valid'],
            'has_candidate_nik' => $candidateNik !== null,
        ];
    }

    /**
     * Confidence of one process-to-case attachment. Name + exact unit stays
     * MEDIUM by product rule; it never changes Consumer identity/confidence.
     *
     * @param  array<string, mixed>  $values
     * @param  array<string, mixed>  $case
     */
    private function processRowConfidence(array $values, array $case): MappingConfidence
    {
        $nik = $this->normalizer->nik($values['nik'] ?? null);
        if ($nik['valid'] && $case['nik_normalized'] === $nik['value']) {
            return MappingConfidence::Exact;
        }

        $legacyLink = $this->normalizer->text($values['legacy_consumer_id'] ?? $values['legacy_id'] ?? null);
        if ($legacyLink !== null && $case['legacy_consumer_id'] === $legacyLink) {
            return MappingConfidence::High;
        }

        return MappingConfidence::Medium;
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
        $case['process_confidence'][$sheet.':'.$row['row']] = $this->processRowConfidence($values, $case)->value;

        $legacyLink = $this->normalizer->text($values['legacy_consumer_id'] ?? $values['legacy_id'] ?? null);
        if ($legacyLink !== null && preg_match('/(\d{16})$/', $legacyLink, $match) === 1 && $this->normalizer->nik($values['nik'] ?? null)['valid'] === false) {
            $case['evidence'][] = 'CANDIDATE_NIK_FROM_GENERATED_ID:'.$match[1].':'.$sheet.':'.$row['row'];
        }

        foreach (self::DATE_FIELDS[$sheet] ?? [] as $field) {
            if (array_key_exists($field, $values)) {
                $date = $this->firstDate($values, [$field], $sheet, $row['row'], $exceptions);
                if ($date !== null) {
                    $case['dates'][$sheet.':'.$row['row'].':'.$field] = $date;
                }
            }
        }

        if ($sheet === 'proses_bank') {
            $result = $this->normalizer->statusValue($values['result'] ?? $values['status'] ?? null) ?? '';
            // CASH is accepted only as an observed legacy path marker; it is
            // still reported separately when stored as fake SP3K/bank data.
            if ($result === '') {
                $this->exception($exceptions, AuditExceptionCode::MissingProcessStatus, $sheet, $row['row'], 'Status/respons bank kosong pada row proses substantif.');
            } elseif (! in_array($result, ['PROCESS', 'REVISION', 'APPROVED', 'REJECTED', 'CASH'], true)) {
                $this->exception($exceptions, AuditExceptionCode::UnknownStatusValue, $sheet, $row['row'], "Status bank tidak dikenal: {$result}");
            }
            $sp3k = Str::upper($this->normalizer->document($values['sp3k_number'] ?? null) ?? '');
            if ($sp3k === 'CASH') {
                $this->exception($exceptions, AuditExceptionCode::CashFakeSp3k, $sheet, $row['row'], 'Literal CASH adalah placeholder, bukan SP3K.');
            } elseif (in_array($sp3k, ['REJECT', 'REJECTED'], true)) {
                $this->exception($exceptions, AuditExceptionCode::PlaceholderSp3kValue, $sheet, $row['row'], "Literal {$sp3k} adalah status, bukan nomor SP3K.");
            }

            if (in_array($result, ['PROCESS', 'REVISION', 'APPROVED', 'REJECTED'], true)) {
                $case['strong_kpr_evidence'][] = 'BANK_RESPONSE:'.$result.':'.$sheet.':'.$row['row'];
            }
            if ($sp3k !== '' && ! in_array($sp3k, ['CASH', 'REJECT', 'REJECTED'], true)) {
                $case['strong_kpr_evidence'][] = 'VALID_SP3K_CANDIDATE:'.$sheet.':'.$row['row'];
            }
        }

        if ($sheet === 'bi_checking') {
            $result = $this->normalizer->statusValue($values['result'] ?? $values['status'] ?? null) ?? '';
            if ($result === '') {
                $this->exception($exceptions, AuditExceptionCode::MissingProcessStatus, $sheet, $row['row'], 'Hasil BI kosong pada row proses substantif.');
            } elseif (! in_array($result, ['CLEAR', 'REVIEW', 'REJECTED'], true)) {
                $this->exception($exceptions, AuditExceptionCode::UnknownStatusValue, $sheet, $row['row'], "Hasil BI tidak dikenal: {$result}");
            }
        }

        if ($sheet === 'bast') {
            $explicitConflict = in_array($case['lifecycle_status'], ['MUNDUR', 'REJECT', 'PINDAH_KAVLING'], true);
            if ($explicitConflict) {
                $this->exception(
                    $exceptions,
                    AuditExceptionCode::LifecycleConflict,
                    $sheet,
                    $row['row'],
                    "BAST valid bertentangan dengan lifecycle eksplisit {$case['lifecycle_status']}; tidak di-override.",
                );
            } elseif (! in_array($case['lifecycle_source'], ['ACTIVE', 'COMPLETED'], true)) {
                // Blank/descriptive lifecycle (e.g. "Lanjut") plus a confidently
                // linked BAST is stronger completion evidence.
                $case['lifecycle_status'] = 'COMPLETED';
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

        $this->recordHistoryPayload($sheet, $row, $case, $exceptions);
    }

    /**
     * @param  array<string, mixed>  $row
     * @param  array<string, mixed>  $case
     * @param  array<int, array<string, mixed>>  $exceptions
     */
    private function recordHistoryPayload(string $sheet, array $row, array &$case, array &$exceptions): void
    {
        $values = $row['values'];
        $legacyLink = $this->normalizer->text($values['legacy_consumer_id'] ?? $values['legacy_id'] ?? null);

        $payload = [
            'source_sheet' => $sheet,
            'source_row' => $row['row'],
            'legacy_id' => $legacyLink,
            'original_values' => $row['original'] ?? [],
        ];

        switch ($sheet) {
            case 'bi_checking':
                $payload['date_raw'] = $values['bi_date'] ?? $values['date'] ?? null;
                $payload['date_normalized'] = $this->firstDate($values, ['bi_date', 'date'], $sheet, $row['row'], $exceptions);
                $payload['result_raw'] = $values['result'] ?? $values['status'] ?? null;
                $payload['result_normalized'] = $this->normalizer->statusValue($values['result'] ?? $values['status'] ?? null);
                $payload['notes'] = $this->normalizer->text($values['notes'] ?? null);
                break;

            case 'psjb':
                $payload['psjb_number'] = $this->normalizer->document($values['psjb_number'] ?? null);
                $payload['date_raw'] = $values['psjb_date'] ?? $values['date'] ?? null;
                $payload['date_normalized'] = $this->firstDate($values, ['psjb_date', 'date'], $sheet, $row['row'], $exceptions);
                $payload['status'] = Str::upper($this->normalizer->text($values['status'] ?? null) ?? 'ACTIVE');
                $payload['notes'] = $this->normalizer->text($values['notes'] ?? null);
                break;

            case 'pemberkasan':
                $payload['bank_name'] = $this->normalizer->text($values['bank'] ?? null);
                $payload['date_raw'] = $values['submission_date'] ?? $values['date'] ?? null;
                $payload['date_normalized'] = $this->firstDate($values, ['submission_date', 'date'], $sheet, $row['row'], $exceptions);
                $payload['sequence'] = count($case['proposed_history']['pemberkasan'] ?? []) + 1;
                $payload['notes'] = $this->normalizer->text($values['notes'] ?? null);
                break;

            case 'proses_bank':
                $sp3kRaw = $this->normalizer->document($values['sp3k_number'] ?? null);
                $sp3kUpper = Str::upper($sp3kRaw ?? '');
                $validSp3k = ($sp3kRaw !== null && ! in_array($sp3kUpper, ['CASH', 'REJECT', 'REJECTED'], true)) ? $sp3kRaw : null;
                $payload['bank_name'] = $this->normalizer->text($values['bank'] ?? null);
                $payload['response_raw'] = $values['result'] ?? $values['status'] ?? null;
                $payload['response_normalized'] = $this->normalizer->statusValue($values['result'] ?? $values['status'] ?? null);
                $payload['response_date_raw'] = $values['response_date'] ?? $values['date'] ?? null;
                $payload['response_date_normalized'] = $this->firstDate($values, ['response_date', 'date'], $sheet, $row['row'], $exceptions);
                $payload['sp3k_number'] = $validSp3k;
                $payload['sp3k_date_raw'] = $values['sp3k_date'] ?? null;
                $payload['sp3k_date_normalized'] = $this->firstDate($values, ['sp3k_date'], $sheet, $row['row'], $exceptions);
                $payload['is_authoritative'] = false;
                $payload['notes'] = $this->normalizer->text($values['notes'] ?? null);
                break;

            case 'ppjb_dev':
                $payload['document_number'] = $this->normalizer->document($values['ppjb_number'] ?? null);
                $payload['date_raw'] = $values['ppjb_date'] ?? $values['date'] ?? null;
                $payload['date_normalized'] = $this->firstDate($values, ['ppjb_date', 'date'], $sheet, $row['row'], $exceptions);
                $payload['notes'] = $this->normalizer->text($values['notes'] ?? null);
                break;

            case 'akad':
                $payload['document_number'] = $this->normalizer->document($values['akad_number'] ?? null);
                $payload['date_raw'] = $values['akad_date'] ?? $values['date'] ?? null;
                $payload['date_normalized'] = $this->firstDate($values, ['akad_date', 'date'], $sheet, $row['row'], $exceptions);
                $payload['notes'] = $this->normalizer->text($values['notes'] ?? null);
                break;

            case 'bast':
                $payload['document_number'] = $this->normalizer->document($values['bast_number'] ?? null);
                $payload['date_raw'] = $values['bast_date'] ?? $values['date'] ?? null;
                $payload['date_normalized'] = $this->firstDate($values, ['bast_date', 'date'], $sheet, $row['row'], $exceptions);
                $payload['status'] = Str::upper($this->normalizer->text($values['status'] ?? null) ?? 'COMPLETED');
                $payload['notes'] = $this->normalizer->text($values['notes'] ?? null);
                break;
        }

        $case['proposed_history'][$sheet][] = $payload;
    }

    /** @param array<int, array<string, mixed>> $cases */
    private function markAuthoritativeProsesBank(array &$cases): void
    {
        foreach ($cases as &$case) {
            $pbRows = $case['proposed_history']['proses_bank'] ?? [];
            if ($pbRows === []) {
                continue;
            }

            $approvedIndices = [];
            foreach ($pbRows as $idx => $pb) {
                if (($pb['response_normalized'] ?? null) === 'APPROVED' && ($pb['sp3k_number'] ?? null) !== null) {
                    $approvedIndices[] = $idx;
                }
            }

            if (count($approvedIndices) === 1) {
                $case['proposed_history']['proses_bank'][$approvedIndices[0]]['is_authoritative'] = true;
            } elseif (count($approvedIndices) > 1) {
                $lastIdx = end($approvedIndices);
                $case['proposed_history']['proses_bank'][$lastIdx]['is_authoritative'] = true;
            }
        }
    }

    /**
     * Infer KPR only when an unresolved case has both a linked submission and
     * a canonical non-CASH bank response/SP3K candidate. Explicit status_cash
     * evidence always wins and is never treated as equivalent to inference.
     *
     * @param  array<int, array<string, mixed>>  $cases
     *
     * @param-out array<int, array<string, mixed>> $cases
     *
     * @param  array<int, array<string, mixed>>  $exceptions
     */
    private function resolveFinancingFromDownstreamEvidence(array &$cases, array &$exceptions): void
    {
        foreach ($cases as &$case) {
            if ($case['financing'] !== 'UNRESOLVED') {
                continue;
            }

            $hasSubmission = ! empty($case['process_rows']['pemberkasan']);
            $evidence = array_values(array_unique($case['strong_kpr_evidence'] ?? []));

            if ($hasSubmission && $evidence !== []) {
                $case['financing'] = 'KPR_SUBSIDI';
                $case['financing_confidence'] = MappingConfidence::High->value;
                $case['financing_evidence'] = [
                    'INFERRED_KPR_FROM_LINKED_SUBMISSION_AND_BANK_CHAIN',
                    ...$evidence,
                ];

                continue;
            }

            $this->exception(
                $exceptions,
                AuditExceptionCode::FinancingUnresolved,
                'data_konsumen',
                $case['process_rows']['data_konsumen'][0],
                'Financing tetap UNRESOLVED; tidak ada chain submission + bank evidence yang kuat.',
            );
        }
        unset($case);
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
        $order = ['consumer', 'bi_checking', 'psjb', 'bank_chain', 'ppjb_dev', 'akad', 'bast'];
        foreach ($cases as $case) {
            $previousDate = null;
            $previousStage = null;
            foreach ($order as $stage) {
                $dates = [];
                foreach ($case['dates'] as $key => $date) {
                    $matches = match ($stage) {
                        'consumer' => $key === 'consumer',
                        'bank_chain' => str_starts_with((string) $key, 'pemberkasan:') || str_starts_with((string) $key, 'proses_bank:'),
                        default => str_starts_with((string) $key, $stage.':'),
                    };
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

    /**
     * Migration-readiness severity pass. Enriches exceptions without erasing
     * them, recalculates readiness classification, and maps each blocker back
     * to the distinct Sales Case candidate it affects.
     *
     * @param  array<string, mixed>  $sheets
     * @param  array<int, array<string, mixed>>  $cases
     * @param  array<int, array<string, mixed>>  $exceptions
     * @param  array<int, array<string, mixed>>  $duplicates
     * @param  array<int, array<string, mixed>>  $unresolved
     * @return array<string, mixed>
     */
    private function migrationAnalysis(array $sheets, array $cases, array $exceptions, array $duplicates, array $unresolved): array
    {
        $transactional = collect(self::TRANSACTION_SHEETS);
        $derivedDuplicateCount = 0;
        $transactionalExactDuplicates = 0;

        $caseByRow = [];
        foreach ($cases as $case) {
            foreach ($case['process_rows'] as $sheet => $rows) {
                foreach ($rows as $row) {
                    $caseByRow[$sheet.'|'.$row][] = $case;
                }
            }
        }

        // Row-value lookup keyed by sheet|row for authoritative determination.
        $valueByRow = [];
        foreach ($sheets as $sheetName => $sheet) {
            foreach ($this->rowsFromSheet($sheet) as $row) {
                $valueByRow[$sheetName.'|'.$row['row']] = $row['values'];
            }
        }

        $caseBlockers = [];
        $caseReviews = [];
        $reviewByEntityCode = [];
        $blockerByEntityCode = [];
        $candidateExceptions = [];
        $transactionalWarnings = 0;
        $derivedDiagnosticWarnings = 0;
        $severityCounts = [MigrationSeverity::Warning->value => 0, MigrationSeverity::Review->value => 0, MigrationSeverity::Blocking->value => 0];

        foreach ($exceptions as $exception) {
            $code = $exception['code'];
            $entity = $exception['sheet'] ?? 'unknown';
            $rowKey = $entity.'|'.($exception['row'] ?? 0);
            $isTransactional = $transactional->contains($entity);
            $linkedCases = $caseByRow[$rowKey] ?? [];
            $uniquelyResolved = count($linkedCases) === 1;
            $case = $linkedCases[0] ?? null;
            $values = $valueByRow[$rowKey] ?? [];

            $severity = $this->entityAwareSeverity($code, $entity, $values, $case, $uniquelyResolved);
            $derivedDiagnostic = ! $isTransactional;

            // Only transactional exceptions become candidate-level records.
            // Derived/pivot noise never pollutes the candidate dashboard.
            if ($isTransactional) {
                foreach ($linkedCases as $linkedCase) {
                    $candidateExceptions[] = [
                        'candidate_key' => $linkedCase['candidate_key'],
                        'code' => $code,
                        'severity' => $severity->value,
                        'source_sheet' => $entity,
                        'source_row' => $exception['row'] ?? null,
                        'entity_type' => $entity,
                        'message' => $exception['message'] ?? '',
                        'evidence' => $this->candidateExceptionEvidence($exception, $values),
                    ];
                }
            }

            $severityCounts[$severity->value]++;
            if ($severity === MigrationSeverity::Warning) {
                if ($isTransactional) {
                    $transactionalWarnings++;
                } else {
                    $derivedDiagnosticWarnings++;
                }
            }

            if ($severity === MigrationSeverity::Blocking) {
                $blockerByEntityCode[$entity][$code] = ($blockerByEntityCode[$entity][$code] ?? 0) + 1;
                foreach ($linkedCases as $linkedCase) {
                    $caseBlockers[$linkedCase['candidate_key']][$code] = true;
                }
            } elseif ($severity === MigrationSeverity::Review) {
                $reviewByEntityCode[$entity][$code] = ($reviewByEntityCode[$entity][$code] ?? 0) + 1;
                foreach ($linkedCases as $linkedCase) {
                    $caseReviews[$linkedCase['candidate_key']][$code] = true;
                }
            }

            if ($code === AuditExceptionCode::ExactRowDuplicate->value) {
                if ($isTransactional) {
                    $transactionalExactDuplicates++;
                } else {
                    $derivedDuplicateCount++;
                }
            }
        }

        $blockerMatrix = [];
        $blockerCardinality = [1 => 0, 2 => 0, 3 => 0];
        foreach ($caseBlockers as $candidateKey => $codes) {
            $reasonCount = count($codes);
            $blockerMatrix[$candidateKey] = [
                'reason_count' => $reasonCount,
                'reasons' => array_keys($codes),
            ];
            $blockerCardinality[min($reasonCount, 3)]++;
        }

        $auto = [];
        $review = [];
        $blocked = [];
        foreach ($cases as $case) {
            $key = $case['candidate_key'];
            if (isset($caseBlockers[$key]) || $case['financing'] === 'UNRESOLVED') {
                $blocked[] = $case;

                continue;
            }

            if (isset($caseReviews[$key]) || $case['confidence'] === MappingConfidence::Medium->value) {
                $review[] = $case;

                continue;
            }

            $auto[] = $case;
        }

        $ambiguousBreakdown = collect($unresolved)
            ->where('reason', 'AMBIGUOUS')
            ->groupBy(fn (array $row): string => $row['ambiguity_reason'] ?? 'OTHER')
            ->map(fn ($group): int => $group->count())
            ->sortKeys()
            ->all();

        $safeDeterministic = collect($unresolved)
            ->where('reason', 'AMBIGUOUS')
            ->filter(fn (array $row): bool => ($row['combined_candidate_count'] ?? 0) === 1
                && (($row['has_exact_nik'] ?? false) === true || ($row['has_legacy_link'] ?? false) === true))
            ->values()
            ->all();

        $downstream = collect($unresolved)->countBy('reason')->all();

        $candidateAnalysis = [];
        foreach ($cases as $case) {
            $key = $case['candidate_key'];
            $candidateAnalysis[$key] = [
                'readiness' => match (true) {
                    isset($caseBlockers[$key]), $case['financing'] === 'UNRESOLVED' => 'BLOCKED',
                    isset($caseReviews[$key]), $case['confidence'] === MappingConfidence::Medium->value => 'REVIEW',
                    default => 'AUTO',
                },
                'confidence' => $case['confidence'],
                'financing' => $case['financing'],
                'lifecycle' => $case['lifecycle_status'],
                'blocker_count' => isset($caseBlockers[$key]) ? count($caseBlockers[$key]) : 0,
                'review_count' => isset($caseReviews[$key]) ? count($caseReviews[$key]) : 0,
            ];
        }

        return [
            'exception_severity_counts' => $severityCounts,
            'derived_reconciliation_duplicate_count' => $derivedDuplicateCount,
            'transactional_exact_duplicate_count' => $transactionalExactDuplicates,
            'transactional_warnings' => $transactionalWarnings,
            'derived_diagnostic_warnings' => $derivedDiagnosticWarnings,
            'distinct_blocked_sales_cases' => count($blockerMatrix),
            'distinct_review_sales_cases' => count($caseReviews),
            'blocker_matrix' => $blockerMatrix,
            'blocker_cardinality' => $blockerCardinality,
            'blocker_by_entity_code' => $blockerByEntityCode,
            'review_by_entity_code' => $reviewByEntityCode,
            'blocker_reason_counts' => collect($caseBlockers)->flatMap(fn (array $codes): array => array_keys($codes))->countBy()->sortKeys()->all(),
            'review_reason_counts' => collect($caseReviews)->flatMap(fn (array $codes): array => array_keys($codes))->countBy()->sortKeys()->all(),
            'readiness' => [
                'auto_migratable' => count($auto),
                'review_required' => count($review),
                'blocked' => count($blocked),
            ],
            'sales_case_ambiguous_root_cause' => $ambiguousBreakdown,
            'safe_deterministic_resolution_candidates' => $safeDeterministic,
            'candidate_exceptions' => $candidateExceptions,
            'candidate_analysis' => $candidateAnalysis,
            'downstream_rows' => [
                'attached' => collect($cases)->flatMap(fn (array $case): array => array_values($case['process_rows'] ?? []))->flatten()->count(),
                'ambiguous' => $downstream['AMBIGUOUS'] ?? 0,
                'orphan' => $downstream['UNRESOLVED'] ?? 0,
            ],
        ];
    }

    /**
     * @param  array<string, mixed>  $exception
     * @param  array<string, mixed>  $values
     * @return array<string, mixed>
     */
    private function candidateExceptionEvidence(array $exception, array $values): array
    {
        $evidence = ['code' => $exception['code']];
        foreach (['nik', 'name', 'id_kavling', 'bank', 'sp3k_number', 'result', 'status', 'status_cash'] as $field) {
            if (array_key_exists($field, $values) && $values[$field] !== null && $values[$field] !== '') {
                $evidence[$field] = $values[$field];
            }
        }

        return $evidence;
    }

    /**
     * Entity-aware severity. Business significance + unique resolution decide
     * review vs blocking; the underlying exception is never erased.
     *
     * @param  array<string, mixed>  $values
     * @param  array<string, mixed>|null  $case
     */
    private function entityAwareSeverity(string $code, string $entity, array $values, ?array $case, bool $uniquelyResolved): MigrationSeverity
    {
        if ($code === AuditExceptionCode::ExactRowDuplicate->value) {
            return MigrationSeverity::Warning;
        }

        if (in_array($code, [
            AuditExceptionCode::CashFakeSp3k->value,
            AuditExceptionCode::DuplicateDocumentNumber->value,
            AuditExceptionCode::PotentialPindahKavling->value,
        ], true)) {
            // CASH fake SP3K and duplicate document numbers are warnings when
            // independent resolution is unambiguous; duplicates never merge.
            return MigrationSeverity::Warning;
        }

        if (in_array($code, [
            AuditExceptionCode::ConsumerNikMissing->value,
            AuditExceptionCode::ConsumerNikInvalid->value,
            AuditExceptionCode::ConsumerIdentityAmbiguous->value,
            AuditExceptionCode::UnitNotFound->value,
            AuditExceptionCode::UnitCodeAmbiguous->value,
            AuditExceptionCode::MultipleActiveUnitCandidates->value,
            AuditExceptionCode::FinancingUnresolved->value,
            AuditExceptionCode::MissingFinancingStatus->value,
            AuditExceptionCode::MultipleAkad->value,
            AuditExceptionCode::BastWithoutAkad->value,
            AuditExceptionCode::PpjbWithoutUpstream->value,
            AuditExceptionCode::MultipleAuthoritativeApprovalCandidates->value,
            AuditExceptionCode::MissingRequiredColumn->value,
            AuditExceptionCode::OrphanBi->value,
            AuditExceptionCode::OrphanPsjb->value,
            AuditExceptionCode::OrphanSubmission->value,
            AuditExceptionCode::OrphanBankProcess->value,
        ], true)) {
            return MigrationSeverity::Blocking;
        }

        if ($code === AuditExceptionCode::SalesCaseAmbiguous->value) {
            // Blocking unless already uniquely resolvable via trusted NIK or
            // explicit trusted legacy linkage.
            if ($uniquelyResolved) {
                return MigrationSeverity::Review;
            }

            return MigrationSeverity::Blocking;
        }

        if ($code === AuditExceptionCode::LifecycleConflict->value) {
            return MigrationSeverity::Blocking;
        }

        $isAuthoritativeDateEntity = in_array($entity, ['akad', 'bast'], true)
            || ($entity === 'proses_bank' && $this->isAuthoritativeApproval($values));

        if ($code === AuditExceptionCode::MissingProcessDate->value) {
            return $isAuthoritativeDateEntity ? MigrationSeverity::Blocking : MigrationSeverity::Review;
        }

        if ($code === AuditExceptionCode::InvalidDate->value) {
            return $isAuthoritativeDateEntity ? MigrationSeverity::Blocking : MigrationSeverity::Review;
        }

        if ($code === AuditExceptionCode::MissingProcessStatus->value) {
            $obscuresAuthoritative = $entity === 'proses_bank'
                || in_array($entity, ['akad', 'bast'], true);

            return $obscuresAuthoritative ? MigrationSeverity::Blocking : MigrationSeverity::Review;
        }

        if ($code === AuditExceptionCode::UnknownStatusValue->value) {
            // Intermediate/historical status is review; authoritative outcome
            // or lifecycle status is blocking.
            $obscuresAuthoritative = $entity === 'proses_bank'
                || in_array($entity, ['akad', 'bast'], true);

            return $obscuresAuthoritative ? MigrationSeverity::Blocking : MigrationSeverity::Review;
        }

        if ($code === AuditExceptionCode::PlaceholderSp3kValue->value) {
            // Placeholder never treated as SP3K; block only when it prevents
            // authoritative approval/downstream determination.
            return ($entity === 'proses_bank' && $this->isAuthoritativeApproval($values))
                ? MigrationSeverity::Blocking
                : MigrationSeverity::Review;
        }

        // Unknown/uncertain severities are surfaced as REVIEW, never silently
        // upgraded to blocking.
        return MigrationSeverity::Review;
    }

    /** @param array<string, mixed> $values */
    private function isAuthoritativeApproval(array $values): bool
    {
        $result = $this->normalizer->statusValue($values['result'] ?? null);
        $sp3k = Str::upper($this->normalizer->document($values['sp3k_number'] ?? null) ?? '');

        return $result === 'APPROVED'
            || ($sp3k !== '' && ! in_array($sp3k, ['CASH', 'REJECT', 'REJECTED'], true));
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
            'sp3k' => collect($this->rowsFromSheet($sheets['proses_bank'] ?? []))->filter(fn (array $row): bool => $this->normalizer->statusValue($row['values']['result'] ?? null) === 'APPROVED' && filled($row['values']['sp3k_number'] ?? null) && ! in_array(Str::upper(trim((string) $row['values']['sp3k_number'])), ['CASH', 'REJECT', 'REJECTED'], true))->count(),
            'active_transactions' => collect($cases)->where('lifecycle_status', 'ACTIVE')->count(),
        ];

        $lifecycle = [
            'legacy_upstream_lanjut_snapshot' => collect($this->rowsFromSheet($sheets['data_konsumen'] ?? []))->filter(fn (array $row): bool => Str::upper($this->normalizer->text($row['values']['lifecycle_status'] ?? null) ?? '') === 'LANJUT')->count(),
            'reconstructed_active' => collect($cases)->where('lifecycle_status', 'ACTIVE')->count(),
            'reconstructed_completed' => collect($cases)->where('lifecycle_status', 'COMPLETED')->count(),
            'reconstructed_closed_non_active' => collect($cases)->whereNotIn('lifecycle_status', ['ACTIVE', 'COMPLETED'])->count(),
        ];

        return [
            'legacy_secondary_baseline' => $legacy,
            'reconstructed_candidates' => $reconstructed,
            'differences' => collect($legacy)->map(fn (int $value, string $key): int => ($reconstructed[$key] ?? 0) - $value)->all(),
            'lifecycle' => $lifecycle,
        ];
    }

    /** @param array<string, mixed> $values
     * @param  array<int, string>  $fields
     * @param  array<int, array<string, mixed>>  $exceptions
     */
    private function firstDate(array $values, array $fields, string $sheet, int $row, array &$exceptions): ?string
    {
        $emitDateExceptions = $sheet !== 'data_konsumen';

        foreach ($fields as $field) {
            if (! array_key_exists($field, $values)) {
                continue;
            }
            $date = $this->normalizer->date($values[$field]);
            if (! $date['valid'] && ! $date['empty'] && $emitDateExceptions) {
                $this->exception($exceptions, AuditExceptionCode::InvalidDate, $sheet, $row, "Tanggal {$field} invalid: ".$this->normalizer->text($values[$field]));
            }
            if ($date['valid'] && ! $date['empty']) {
                return $date['value'];
            }
        }

        if ($emitDateExceptions) {
            // A substantive process row with an entirely blank expected date
            // field is missing evidence — never synthesized or guessed.
            $this->exception($exceptions, AuditExceptionCode::MissingProcessDate, $sheet, $row, 'Tanggal proses kosong ('.implode('/', $fields).').');
        }

        return null;
    }

    /**
     * @param  array<string, mixed>  $values
     * @return array{value: string, confidence: string, evidence: array<int, string>, exception: AuditExceptionCode|null, message: string}
     */
    private function financing(array $values): array
    {
        // Canonical fixtures retain explicit financing support, but the real
        // Jepara contract is status_cash=YA/TIDAK.
        $financing = Str::upper($this->normalizer->text($values['financing'] ?? null) ?? '');
        if (in_array($financing, ['CASH', 'KPR_SUBSIDI'], true)) {
            return [
                'value' => $financing,
                'confidence' => MappingConfidence::Exact->value,
                'evidence' => ['EXPLICIT_FINANCING:'.$financing],
                'exception' => null,
                'message' => '',
            ];
        }

        $cashFlag = Str::upper($this->normalizer->text($values['status_cash'] ?? null) ?? '');

        if ($cashFlag === 'YA') {
            return [
                'value' => 'CASH',
                'confidence' => MappingConfidence::Exact->value,
                'evidence' => ['EXPLICIT_STATUS_CASH:YA'],
                'exception' => null,
                'message' => '',
            ];
        }

        if ($cashFlag === 'TIDAK') {
            return [
                'value' => 'KPR_SUBSIDI',
                'confidence' => MappingConfidence::Exact->value,
                'evidence' => ['EXPLICIT_STATUS_CASH:TIDAK'],
                'exception' => null,
                'message' => '',
            ];
        }

        $missing = $cashFlag === '';

        return [
            'value' => 'UNRESOLVED',
            'confidence' => MappingConfidence::Unresolved->value,
            'evidence' => $missing ? ['STATUS_CASH_MISSING'] : ['STATUS_CASH_UNKNOWN:'.$cashFlag],
            'exception' => $missing ? AuditExceptionCode::MissingFinancingStatus : AuditExceptionCode::FinancingUnresolved,
            'message' => $missing
                ? 'status_cash kosong; financing tidak boleh default ke KPR.'
                : "status_cash tidak dikenal: {$cashFlag}; financing tidak boleh default ke KPR.",
        ];
    }

    /** @param array<string, mixed> $values */
    private function lifecycleStatus(array $values): string
    {
        $value = $this->normalizer->statusValue($values['lifecycle_status'] ?? $values['status'] ?? null) ?? 'ACTIVE';

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
