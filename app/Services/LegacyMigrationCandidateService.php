<?php

namespace App\Services;

use App\MigrationBatchStatus;
use App\Models\LegacyMigrationBatch;
use App\Models\LegacyMigrationCandidate;
use App\Models\LegacyMigrationCandidateException;
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

            foreach ($cases as $candidateKey => $case) {
                $analysisRow = $candidateAnalysis[$candidateKey] ?? ['readiness' => 'BLOCKED', 'blocker_count' => 0, 'review_count' => 0];

                $candidate = LegacyMigrationCandidate::create([
                    'batch_id' => $batch->id,
                    'branch_id' => null,
                    'source_candidate_key' => $candidateKey,
                    'proposed_consumer' => $consumers->get($case['consumer_key'] ?? null, ['candidate_key' => $case['consumer_key'] ?? null]),
                    'proposed_unit' => $units->get($case['unit_key'] ?? null, ['candidate_key' => $case['unit_key'] ?? null]),
                    'proposed_sales_case' => $case,
                    'proposed_history' => $case['process_rows'] ?? [],
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
            }

            return $batch;
        });
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
