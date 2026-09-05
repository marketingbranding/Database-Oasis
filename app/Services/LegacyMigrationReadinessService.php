<?php

namespace App\Services;

use App\Enums\MigrationExceptionSeverity;
use App\MigrationReadiness;
use App\MigrationReviewDecision;
use App\Models\LegacyMigrationBatch;
use App\Models\LegacyMigrationCandidate;

/**
 * Single source of truth for per-candidate readiness. Never trust UI state.
 */
class LegacyMigrationReadinessService
{
    public function calculate(LegacyMigrationCandidate $candidate): MigrationReadiness
    {
        $exceptions = $candidate->exceptions()->get();
        $unresolvedBlockers = $exceptions
            ->where('severity', MigrationExceptionSeverity::Blocking)
            ->reject(fn ($exception) => $this->isResolved($candidate, $exception->code));

        if ($unresolvedBlockers->isNotEmpty()) {
            return MigrationReadiness::Blocked;
        }

        $accepted = $candidate->reviews()
            ->where('decision', MigrationReviewDecision::Accept->value)
            ->where('source_fingerprint', $candidate->batch->source_fingerprint)
            ->where('audit_fingerprint', $candidate->batch->audit_fingerprint)
            ->latest('reviewed_at')
            ->first();

        if ($candidate->readiness === MigrationReadiness::Blocked) {
            return $accepted === null ? MigrationReadiness::Review : $this->autoAfterResolution();
        }

        $unresolvedReviews = $exceptions
            ->where('severity', MigrationExceptionSeverity::Review)
            ->reject(fn ($exception) => $this->isResolved($candidate, $exception->code));

        if (($candidate->readiness === MigrationReadiness::Review || $unresolvedReviews->isNotEmpty()) && $accepted === null) {
            return MigrationReadiness::Review;
        }

        return MigrationReadiness::Auto;
    }

    public function requiresReview(LegacyMigrationCandidate $candidate): bool
    {
        return $this->calculate($candidate) === MigrationReadiness::Review;
    }

    public function isMigrationReady(LegacyMigrationCandidate $candidate): bool
    {
        return $this->calculate($candidate) === MigrationReadiness::Auto;
    }

    /** @return array<string, int> */
    public function recalculateBatch(LegacyMigrationBatch $batch): array
    {
        $counts = [MigrationReadiness::Auto->value => 0, MigrationReadiness::Review->value => 0, MigrationReadiness::Blocked->value => 0];

        foreach ($batch->candidates()->with(['exceptions', 'reviews', 'resolutions', 'batch'])->get() as $candidate) {
            $readiness = $this->calculate($candidate);
            if ($candidate->readiness !== $readiness) {
                $candidate->update(['readiness' => $readiness]);
            }
            $counts[$readiness->value]++;
        }

        return $counts;
    }

    private function isResolved(LegacyMigrationCandidate $candidate, string $code): bool
    {
        return $candidate->resolutions()->where('exception_code', $code)->exists();
    }

    private function autoAfterResolution(): MigrationReadiness
    {
        return MigrationReadiness::Auto;
    }
}
