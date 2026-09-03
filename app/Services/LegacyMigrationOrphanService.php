<?php

namespace App\Services;

use App\Enums\LegacyOrphanDecision;
use App\Enums\LegacyOrphanStatus;
use App\Models\LegacyMigrationCandidate;
use App\Models\LegacyMigrationOrphan;
use App\Models\LegacyMigrationOrphanResolution;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class LegacyMigrationOrphanService
{
    public function resolve(
        LegacyMigrationOrphan $orphan,
        User $user,
        LegacyOrphanDecision $decision,
        string $reason,
        ?LegacyMigrationCandidate $candidate = null,
    ): LegacyMigrationOrphanResolution {
        if ($decision === LegacyOrphanDecision::LinkToCandidate && $candidate === null) {
            throw ValidationException::withMessages(['candidate_id' => 'Linking orphan requires a target candidate.']);
        }

        if ($candidate !== null && $candidate->batch_id !== $orphan->batch_id) {
            throw ValidationException::withMessages(['candidate_id' => 'Candidate must belong to the same batch.']);
        }

        if (! hash_equals($orphan->source_fingerprint, $orphan->batch->source_fingerprint)) {
            throw ValidationException::withMessages(['fingerprint' => 'Orphan source fingerprint is stale.']);
        }

        return DB::transaction(function () use ($orphan, $user, $decision, $reason, $candidate): LegacyMigrationOrphanResolution {
            $resolution = LegacyMigrationOrphanResolution::create([
                'orphan_id' => $orphan->id,
                'target_candidate_id' => $candidate?->id,
                'decision' => $decision,
                'resolution_type' => $decision->value,
                'note' => $reason,
                'decided_by' => $user->id,
                'decided_at' => now(),
                'source_fingerprint' => $orphan->source_fingerprint,
                'audit_fingerprint' => $orphan->audit_fingerprint,
            ]);

            $orphan->update(['status' => match ($decision) {
                LegacyOrphanDecision::LinkToCandidate => LegacyOrphanStatus::Linked,
                LegacyOrphanDecision::ExcludeAsDuplicate, LegacyOrphanDecision::ExcludeAsIrrelevant => LegacyOrphanStatus::Excluded,
                LegacyOrphanDecision::LeaveUnresolved => LegacyOrphanStatus::Unresolved,
            }]);

            return $resolution;
        });
    }
}
