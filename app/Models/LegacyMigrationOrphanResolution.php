<?php

namespace App\Models;

use App\Enums\LegacyOrphanDecision;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['orphan_id', 'decision', 'resolution_type', 'target_candidate_id', 'note', 'decided_by', 'decided_at', 'source_fingerprint', 'audit_fingerprint'])]
class LegacyMigrationOrphanResolution extends Model
{
    protected function casts(): array
    {
        return [
            'decision' => LegacyOrphanDecision::class,
            'decided_at' => 'datetime',
        ];
    }

    /** @return BelongsTo<LegacyMigrationOrphan, $this> */
    public function orphan(): BelongsTo
    {
        return $this->belongsTo(LegacyMigrationOrphan::class);
    }

    /** @return BelongsTo<LegacyMigrationCandidate, $this> */
    public function targetCandidate(): BelongsTo
    {
        return $this->belongsTo(LegacyMigrationCandidate::class, 'target_candidate_id');
    }

    /** @return BelongsTo<User, $this> */
    public function decidedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'decided_by');
    }
}
