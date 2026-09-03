<?php

namespace App\Models;

use App\MigrationReviewDecision;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['candidate_id', 'decision', 'reviewed_by', 'reviewed_at', 'reason', 'source_fingerprint', 'audit_fingerprint'])]
class LegacyMigrationReview extends Model
{
    protected function casts(): array
    {
        return [
            'decision' => MigrationReviewDecision::class,
            'reviewed_at' => 'datetime',
        ];
    }

    /** @return BelongsTo<LegacyMigrationCandidate, $this> */
    public function candidate(): BelongsTo
    {
        return $this->belongsTo(LegacyMigrationCandidate::class);
    }

    /** @return BelongsTo<User, $this> */
    public function reviewer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reviewed_by');
    }
}
