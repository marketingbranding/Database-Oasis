<?php

namespace App\Services;

use App\Enums\MigrationExceptionSeverity;
use App\LegacyMigration\AuditExceptionCode;
use App\MigrationBatchStatus;
use App\MigrationReadiness;
use App\Models\Bank;
use App\Models\LegacyMigrationBatch;
use App\Models\LegacyMigrationCandidate;
use App\Models\LegacyMigrationCandidateException;
use App\Models\LegacyMigrationOrphan;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use RuntimeException;

class LegacyMigrationCandidateService
{
    public function buildFromReport(string $reportDirectory, User $user): LegacyMigrationBatch
    {
        $summaryPath = rtrim($reportDirectory, DIRECTORY_SEPARATOR).DIRECTORY_SEPARATOR.'summary.json';
        if (! is_file($summaryPath)) {
            throw new RuntimeException("Audit summary tidak ditemukan: {$summaryPath}");
        }

        $report = json_decode(file_get_contents($summaryPath), true, 512, JSON_THROW_ON_ERROR);
        $meta = $report['meta'];
        $analysis = $report['migration_analysis'];

        $salesCases = $report['sales_cases'];
        $consumersRaw = $report['consumers'];
        $unitsRaw = $report['units'];
        $candidateExceptionsRaw = $analysis['candidate_exceptions'];
        /** @var array<int, array<string, mixed>> $salesCases */
        /** @var array<int, array<string, mixed>> $consumersRaw */
        /** @var array<int, array<string, mixed>> $unitsRaw */
        /** @var array<int, array<string, mixed>> $candidateExceptionsRaw */
        $cases = collect($salesCases)->keyBy('candidate_key');
        $consumers = collect($consumersRaw)->keyBy('candidate_key');
        $units = collect($unitsRaw)->keyBy('candidate_key');
        $candidateExceptions = collect($candidateExceptionsRaw)->groupBy('candidate_key');
        $candidateAnalysis = $analysis['candidate_analysis'];

        return DB::transaction(function () use ($meta, $report, $cases, $consumers, $units, $candidateExceptions, $candidateAnalysis, $user): LegacyMigrationBatch {
            $batch = LegacyMigrationBatch::create([
                'branch_id' => null,
                'source_filename' => basename((string) $meta['source']),
                'source_fingerprint' => (string) $meta['source_fingerprint'],
                'audit_fingerprint' => (string) $meta['audit_fingerprint'],
                'source_row_counts' => $report['summary']['legacy_rows_by_sheet'],
                'status' => MigrationBatchStatus::Audited,
                'created_by' => $user->id,
            ]);

            $this->applyApprovedJeparaBankMappings($batch, $user);

            foreach ($cases as $candidateKey => $case) {
                $analysisRow = $candidateAnalysis[$candidateKey] ?? ['readiness' => 'BLOCKED', 'blocker_count' => 0, 'review_count' => 0];

                $candidate = LegacyMigrationCandidate::create([
                    'batch_id' => $batch->id,
                    'branch_id' => null,
                    'source_candidate_key' => $candidateKey,
                    'proposed_consumer' => $consumers->get($case['consumer_key'] ?? null, ['candidate_key' => $case['consumer_key'] ?? null]),
                    'proposed_unit' => $units->get($case['unit_key'] ?? null, ['candidate_key' => $case['unit_key'] ?? null]),
                    'proposed_sales_case' => $case,
                    'proposed_history' => $case['proposed_history'] ?? $case['process_rows'] ?? [],
                    'confidence' => $case['confidence'],
                    'readiness' => $analysisRow['readiness'],
                    'lifecycle' => $case['lifecycle_status'],
                    'financing_type' => $case['financing'],
                    'source_evidence' => [
                        'case_evidence' => $case['evidence'] ?? [],
                        'financing_evidence' => $case['financing_evidence'] ?? [],
                    ],
                    'source_fingerprint' => (string) $meta['source_fingerprint'],
                ]);

                foreach ($candidateExceptions->get($candidateKey, []) as $exception) {
                    LegacyMigrationCandidateException::create([
                        'candidate_id' => $candidate->id,
                        'code' => $exception['code'],
                        'severity' => $exception['severity'],
                        'source_sheet' => $exception['source_sheet'],
                        'source_row' => $exception['source_row'],
                        'entity_type' => $exception['entity_type'],
                        'message' => $exception['message'],
                        'evidence' => $exception['evidence'],
                    ]);
                }

                if (! in_array($candidate->financing_type, ['CASH'], true)) {
                    foreach ($this->bankMappingExceptions($batch, $case) as $bankException) {
                        LegacyMigrationCandidateException::create([
                            'candidate_id' => $candidate->id,
                            'code' => $bankException['code'],
                            'severity' => MigrationExceptionSeverity::Blocking->value,
                            'source_sheet' => $bankException['source_sheet'],
                            'source_row' => $bankException['source_row'],
                            'entity_type' => $bankException['source_sheet'],
                            'message' => "Bank '{$bankException['bank_name']}' tidak dapat di-resolve secara deterministik.",
                            'evidence' => [
                                'code' => $bankException['code'],
                                'bank_name' => $bankException['bank_name'],
                            ],
                        ]);
                    }
                }

                $previousKey = $case['previous_case_candidate'] ?? null;
                if ($previousKey !== null && ($candidateAnalysis[$previousKey]['readiness'] ?? 'BLOCKED') !== 'AUTO') {
                    LegacyMigrationCandidateException::create([
                        'candidate_id' => $candidate->id,
                        'code' => AuditExceptionCode::PreviousCaseDependencyNotReady->value,
                        'severity' => MigrationExceptionSeverity::Blocking->value,
                        'source_sheet' => 'data_konsumen',
                        'source_row' => $case['process_rows']['data_konsumen'][0] ?? null,
                        'entity_type' => 'data_konsumen',
                        'message' => "Kandidat predecessor {$previousKey} belum AUTO/eligible dalam plan.",
                        'evidence' => ['previous_case_candidate' => $previousKey],
                    ]);
                }

                // Newly added BLOCKING exceptions (bank mapping, pindah
                // dependency) were not present when the audit assigned stored
                // readiness. Downgrade so stored readiness stays consistent
                // with the recalculated readiness layer.
                $hasBlocking = $candidate->exceptions()
                    ->where('severity', MigrationExceptionSeverity::Blocking->value)
                    ->exists();
                if ($hasBlocking && in_array($candidate->readiness->value, ['AUTO', 'REVIEW'], true)) {
                    $candidate->update(['readiness' => MigrationReadiness::Blocked]);
                }
            }

            foreach ($report['unresolved_records'] as $orphan) {
                $sheet = (string) ($orphan['sheet'] ?? 'unknown');
                $blocking = in_array($sheet, ['proses_bank', 'akad', 'bast'], true)
                    || ($orphan['reason'] ?? null) === 'AMBIGUOUS';

                LegacyMigrationOrphan::create([
                    'batch_id' => $batch->id,
                    'branch_id' => null,
                    'source_sheet' => $sheet,
                    'source_row' => $orphan['row'] ?? null,
                    'source_fingerprint' => (string) $meta['source_fingerprint'],
                    'audit_fingerprint' => (string) $meta['audit_fingerprint'],
                    'orphan_code' => $this->orphanCode($sheet, (string) ($orphan['reason'] ?? 'UNRESOLVED')),
                    'severity' => $blocking ? 'BLOCKING' : 'REVIEW',
                    'normalized_evidence' => $orphan,
                    'candidate_matches' => [
                        'count' => $orphan['candidate_count'] ?? 0,
                        'matches' => $orphan['candidate_matches'] ?? [],
                        'consumer_candidates' => $orphan['consumer_candidate_count'] ?? 0,
                        'unit_candidates' => $orphan['unit_candidate_count'] ?? 0,
                    ],
                    'status' => 'PENDING',
                ]);
            }

            return $batch;
        });
    }

    private function applyApprovedJeparaBankMappings(LegacyMigrationBatch $batch, User $user): void
    {
        $service = app(LegacyMigrationBankMappingService::class);
        foreach ([
            'BTN' => 'BTN',
            'BSN' => 'BTNS',
            'BANK BRI' => 'BRI',
            'BANK JATENG' => 'BJTG',
            'BNI' => 'BNI',
        ] as $raw => $code) {
            $bank = Bank::where('code', $code)->where('is_active', true)->first();
            if ($bank !== null) {
                $service->approve($batch, $raw, $bank, $user, "Approved Jepara canonical alias: {$raw} → {$bank->name}");
            }
        }
    }

    /** @param array<string, mixed> $case
     * @return array<int, array{code: string, source_sheet: string, source_row: int|null, bank_name: string}>
     */
    private function bankMappingExceptions(LegacyMigrationBatch $batch, array $case): array
    {
        $rows = [];
        $mappingService = app(LegacyMigrationBankMappingService::class);

        foreach (['pemberkasan', 'proses_bank'] as $sheet) {
            foreach ($case['proposed_history'][$sheet] ?? [] as $row) {
                $bankName = $row['bank_name'] ?? null;
                if ($bankName === null || trim((string) $bankName) === '' || mb_strtolower(trim((string) $bankName)) === 'cash') {
                    continue;
                }

                if ($mappingService->resolve($batch, (string) $bankName) !== null) {
                    continue;
                }

                $distance = Bank::whereRaw('LOWER(name) = ?', [mb_strtolower(trim((string) $bankName))])
                    ->orWhereRaw('LOWER(code) = ?', [mb_strtolower(trim((string) $bankName))])
                    ->count();

                if ($distance === 0) {
                    $rows[] = [
                        'code' => AuditExceptionCode::BankNotFound->value,
                        'source_sheet' => $sheet,
                        'source_row' => $row['source_row'] ?? null,
                        'bank_name' => trim((string) $bankName),
                    ];
                } elseif ($distance > 1) {
                    $rows[] = [
                        'code' => AuditExceptionCode::BankAmbiguous->value,
                        'source_sheet' => $sheet,
                        'source_row' => $row['source_row'] ?? null,
                        'bank_name' => trim((string) $bankName),
                    ];
                }
            }
        }

        return $rows;
    }

    private function orphanCode(string $sheet, string $reason): string
    {
        if ($reason === 'AMBIGUOUS') {
            return 'SALES_CASE_AMBIGUOUS';
        }

        return match ($sheet) {
            'bi_checking' => 'ORPHAN_BI',
            'psjb' => 'ORPHAN_PSJB',
            'pemberkasan' => 'ORPHAN_SUBMISSION',
            'proses_bank' => 'ORPHAN_BANK_PROCESS',
            'ppjb_dev' => 'ORPHAN_PPJB',
            'akad' => 'ORPHAN_AKAD',
            'bast' => 'ORPHAN_BAST',
            default => 'ORPHAN_OTHER',
        };
    }

    /** @return array{source_fingerprint: string, audit_fingerprint: string} */
    public function fingerprintSignature(LegacyMigrationBatch $batch): array
    {
        return [
            'source_fingerprint' => $batch->source_fingerprint,
            'audit_fingerprint' => $batch->audit_fingerprint,
        ];
    }
}
