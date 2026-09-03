<?php

namespace App\Models;

use App\Enums\LegacyOrphanStatus;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable(['batch_id', 'branch_id', 'source_sheet', 'source_row', 'source_fingerprint', 'orphan_code', 'severity', 'normalized_evidence', 'candidate_matches', 'status', 'audit_fingerprint'])]
class LegacyMigrationOrphan extends Model
{
    protected function casts(): array
    {
        return [
            'source_row' => 'integer',
            'normalized_evidence' => 'array',
            'candidate_matches' => 'array',
            'status' => LegacyOrphanStatus::class,
        ];
    }

    /** @return BelongsTo<LegacyMigrationBatch, $this> */
    public function batch(): BelongsTo
    {
        return $this->belongsTo(LegacyMigrationBatch::class);
    }

    /** @return BelongsTo<Branch, $this> */
    public function branch(): BelongsTo
    {
        return $this->belongsTo(Branch::class);
    }

    /** @return HasMany<LegacyMigrationOrphanResolution, $this> */
    public function resolutions(): HasMany
    {
        return $this->hasMany(LegacyMigrationOrphanResolution::class, 'orphan_id');
    }
}
