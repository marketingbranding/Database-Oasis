<?php

namespace App\Models;

use App\MigrationReadiness;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable(['batch_id', 'branch_id', 'source_candidate_key', 'proposed_consumer', 'proposed_unit', 'proposed_sales_case', 'proposed_history', 'confidence', 'readiness', 'lifecycle', 'financing_type', 'source_evidence', 'source_fingerprint'])]
class LegacyMigrationCandidate extends Model
{
    protected function casts(): array
    {
        return [
            'proposed_consumer' => 'array',
            'proposed_unit' => 'array',
            'proposed_sales_case' => 'array',
            'proposed_history' => 'array',
            'source_evidence' => 'array',
            'readiness' => MigrationReadiness::class,
        ];
    }

    /** @return BelongsTo<LegacyMigrationBatch, $this> */
    public function batch(): BelongsTo
    {
        return $this->belongsTo(LegacyMigrationBatch::class, 'batch_id');
    }

    /** @return BelongsTo<Branch, $this> */
    public function branch(): BelongsTo
    {
        return $this->belongsTo(Branch::class);
    }

    /** @return HasMany<LegacyMigrationCandidateException, $this> */
    public function exceptions(): HasMany
    {
        return $this->hasMany(LegacyMigrationCandidateException::class, 'candidate_id');
    }

    /** @return HasMany<LegacyMigrationReview, $this> */
    public function reviews(): HasMany
    {
        return $this->hasMany(LegacyMigrationReview::class, 'candidate_id');
    }

    /** @return HasMany<LegacyMigrationResolution, $this> */
    public function resolutions(): HasMany
    {
        return $this->hasMany(LegacyMigrationResolution::class, 'candidate_id');
    }
}
