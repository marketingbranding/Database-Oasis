<?php

namespace App\Services;

use App\Enums\LegacyResolutionType;
use App\Enums\MigrationExceptionSeverity;
use App\Models\LegacyMigrationBatch;
use App\Models\LegacyMigrationCandidate;
use App\Models\LegacyMigrationResolution;
use App\Models\User;
use Illuminate\Support\Facades\DB;

/**
 * Deterministic (non-human) resolution for authoritative bank status when all
 * approved evidence conditions hold. Records explicit provenance, never
 * classified as a human review decision.
 */
class LegacyDeterministicResolutionService
{
    /** @return array<string, mixed> */
    public function resolveAuthoritativeStatusCandidates(LegacyMigrationBatch $batch): array
    {
        $system = User::query()
            ->whereHas('roles', fn ($query) => $query->whereIn('name', ['Super Admin']))
            ->first();

        $resolved = [];

        $candidates = $batch->candidates()->with('exceptions', 'resolutions')->get();
        foreach ($candidates as $candidate) {
            $hasMissing = $candidate->exceptions
                ->where('code', 'MISSING_PROCESS_STATUS')
                ->where('severity', MigrationExceptionSeverity::Blocking->value)
                ->isNotEmpty();

            if (! $hasMissing) {
                continue;
            }

            if (! $this->isDeterministicallyResolvable($candidate)) {
                continue;
            }

            DB::transaction(function () use ($candidate, $system): void {
                LegacyMigrationResolution::create([
                    'candidate_id' => $candidate->id,
                    'exception_code' => 'MISSING_PROCESS_STATUS',
                    'resolution_type' => LegacyResolutionType::SelectAuthoritativeBankAttempt->value,
                    'selected_value' => ['deterministic' => true, 'reason' => 'APPROVED + valid SP3K + unique bank attempt'],
                    'note' => 'Deterministic resolution (non-human)',
                    'resolved_by' => $system?->id,
                    'resolved_at' => now(),
                    'source_fingerprint' => $candidate->source_fingerprint,
                    'audit_fingerprint' => $candidate->batch->audit_fingerprint,
                ]);
            });

            $resolved[] = $candidate->source_candidate_key;
        }

        return ['resolved' => $resolved];
    }

    private function isDeterministicallyResolvable(LegacyMigrationCandidate $candidate): bool
    {
        /** @var array<int, array<string, mixed>> $attempts */
        $attempts = $candidate->proposed_history['proses_bank'] ?? [];
        if ($attempts === []) {
            return false;
        }

        $authoritative = collect($attempts)
            ->filter(fn (array $row): bool => ($row['is_authoritative'] ?? false) === true);
        if ($authoritative->count() !== 1) {
            return false;
        }

        /** @var array<string, mixed> $row */
        $row = $authoritative->first();

        $sp3k = $row['sp3k_number'] ?? null;

        return ($row['response_normalized'] ?? null) === 'APPROVED'
            && filled($sp3k)
            && ! in_array(mb_strtoupper((string) $sp3k), ['CASH', 'REJECT', 'REJECTED'], true);
    }
}
