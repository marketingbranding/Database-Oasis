<?php

namespace App\Services;

use App\Enums\MigrationExceptionSeverity;
use App\MigrationReadiness;
use App\Models\LegacyMigrationBatch;
use App\Models\LegacyMigrationCandidate;

class LegacyMigrationDryRunService
{
    public function __construct(private LegacyMigrationReadinessService $readiness) {}

    /** @return array<string, mixed> */
    public function plan(LegacyMigrationBatch $batch): array
    {
        $candidates = $batch->candidates()->with(['exceptions', 'reviews', 'resolutions'])->get();

        $rows = [];
        $totals = [
            'eligible' => 0,
            'auto' => 0,
            'review_accepted' => 0,
            'review_unapproved' => 0,
            'blocked' => 0,
            'rejected' => 0,
            'consumer_creates' => 0,
            'consumer_reuses' => 0,
            'unit_matches' => 0,
            'sales_cases' => 0,
            'bi_records' => 0,
            'psjb_records' => 0,
            'pemberkasan_records' => 0,
            'bank_process_records' => 0,
            'authoritative_sp3k' => 0,
            'ppjb_records' => 0,
            'akad_records' => 0,
            'bast_records' => 0,
            'cash_cases' => 0,
            'pindah_kavling_relationships' => 0,
            'units_active' => 0,
            'units_booking' => 0,
            'units_terjual' => 0,
            'invariant_failures' => 0,
        ];

        foreach ($candidates as $candidate) {
            $readiness = $this->readiness->calculate($candidate);
            $plan = $this->planCandidate($candidate, $readiness);

            if ($plan['error'] !== null) {
                $totals['invariant_failures']++;
            } else {
                $this->mergeCounts($totals, $plan['counts']);
            }

            if ($readiness === MigrationReadiness::Blocked) {
                $totals['blocked']++;
            } elseif ($readiness === MigrationReadiness::Review) {
                if ($candidate->reviews()->where('decision', 'ACCEPT')->exists()) {
                    $totals['review_accepted']++;
                } else {
                    $totals['review_unapproved']++;
                }
            }

            if ($candidate->reviews()->where('decision', 'REJECT')->exists()) {
                $totals['rejected']++;
            }

            $rows[] = [
                'candidate_key' => $candidate->source_candidate_key,
                'readiness' => $readiness->value,
                'eligible' => $readiness !== MigrationReadiness::Blocked && $plan['error'] === null,
                'plan' => $plan,
            ];
        }

        $totals['eligible'] = $totals['auto'] + $totals['review_accepted'];

        return [
            'batch_id' => $batch->id,
            'source_fingerprint' => $batch->source_fingerprint,
            'audit_fingerprint' => $batch->audit_fingerprint,
            'totals' => $totals,
            'candidates' => $rows,
        ];
    }

    /** @return array{counts: array<string, int>, error: ?string} */
    private function planCandidate(LegacyMigrationCandidate $candidate, MigrationReadiness $readiness): array
    {
        $zero = $this->zeroCounts();
        if ($readiness !== MigrationReadiness::Auto) {
            return ['counts' => $zero, 'error' => null];
        }

        $case = $candidate->proposed_sales_case;
        $history = $candidate->proposed_history;
        $financing = $candidate->financing_type;

        $error = $this->invariantError($candidate, $case, $financing);
        if ($error !== null) {
            return ['counts' => $zero, 'error' => $error];
        }

        $counts = $zero;
        $counts['eligible'] = 1;
        if ($candidate->readiness === MigrationReadiness::Review) {
            $counts['review_accepted'] = 1;
        } else {
            $counts['auto'] = 1;
        }
        $counts['consumer_creates'] = 1;
        $counts['consumer_reuses'] = 0;
        $counts['unit_matches'] = 1;
        $counts['sales_cases'] = 1;

        $counts['bi_records'] = count($history['bi_checking'] ?? []);
        $counts['psjb_records'] = count($history['psjb'] ?? []);
        $counts['pemberkasan_records'] = count($history['pemberkasan'] ?? []);
        $counts['bank_process_records'] = count($history['proses_bank'] ?? []);
        $counts['authoritative_sp3k'] = $this->authoritativeSp3kCount($candidate);
        $counts['ppjb_records'] = count($history['ppjb_dev'] ?? []);
        $counts['akad_records'] = count($history['akad'] ?? []);
        $counts['bast_records'] = count($history['bast'] ?? []);

        if ($financing === 'CASH') {
            $counts['cash_cases'] = 1;
            if ($counts['pemberkasan_records'] > 0 || $counts['bank_process_records'] > 0 || $counts['authoritative_sp3k'] > 0) {
                return ['counts' => $zero, 'error' => 'CASH candidate carries bank/SP3K evidence'];
            }
        }

        $counts['pindah_kavling_relationships'] = $case['previous_case_candidate'] !== null ? 1 : 0;

        if ($case['lifecycle_status'] === 'ACTIVE') {
            $counts['units_active'] = 1;
        } elseif ($case['lifecycle_status'] === 'COMPLETED') {
            $counts['units_terjual'] = 1;
        } else {
            $counts['units_booking'] = 1;
        }

        return ['counts' => $counts, 'error' => null];
    }

    /** @param array<string, mixed> $case */
    private function invariantError(LegacyMigrationCandidate $candidate, array $case, ?string $financing): ?string
    {
        if ($financing === 'CASH' && $this->authoritativeSp3kCount($candidate) > 0) {
            return 'CASH candidate has fake SP3K evidence';
        }

        if (! in_array($case['lifecycle_status'], ['ACTIVE', 'COMPLETED', 'MUNDUR', 'REJECT', 'PINDAH_KAVLING', 'CANCELLED'], true)) {
            return 'unknown lifecycle status';
        }

        if ($candidate->exceptions()->where('severity', MigrationExceptionSeverity::Blocking)->exists()) {
            return 'unresolved blocking exception';
        }

        return null;
    }

    private function authoritativeSp3kCount(LegacyMigrationCandidate $candidate): int
    {
        $hasFakeSp3k = $candidate->exceptions()->where('code', 'CASH_FAKE_SP3K')->exists();
        $hasBankProcesses = count($candidate->proposed_history['proses_bank'] ?? []) > 0;

        return (! $hasFakeSp3k && $hasBankProcesses) ? 1 : 0;
    }

    /** @return array<string, int> */
    private function zeroCounts(): array
    {
        return [
            'eligible' => 0,
            'auto' => 0,
            'review_accepted' => 0,
            'review_unapproved' => 0,
            'blocked' => 0,
            'rejected' => 0,
            'consumer_creates' => 0,
            'consumer_reuses' => 0,
            'unit_matches' => 0,
            'sales_cases' => 0,
            'bi_records' => 0,
            'psjb_records' => 0,
            'pemberkasan_records' => 0,
            'bank_process_records' => 0,
            'authoritative_sp3k' => 0,
            'ppjb_records' => 0,
            'akad_records' => 0,
            'bast_records' => 0,
            'cash_cases' => 0,
            'pindah_kavling_relationships' => 0,
            'units_active' => 0,
            'units_booking' => 0,
            'units_terjual' => 0,
            'invariant_failures' => 0,
        ];
    }

    /** @param array<string, int> $totals
     * @param  array<string, int>  $counts
     */
    private function mergeCounts(array &$totals, array $counts): void
    {
        foreach ($counts as $key => $value) {
            $totals[$key] = ($totals[$key] ?? 0) + $value;
        }
    }
}
