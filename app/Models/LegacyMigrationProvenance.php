<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['execution_id', 'plan_id', 'operation_id', 'batch_id', 'candidate_id', 'orphan_id', 'source_sheet', 'source_row', 'legacy_id', 'entity_type', 'target_type', 'target_id', 'source_values', 'source_fingerprint', 'audit_fingerprint'])]
class LegacyMigrationProvenance extends Model
{
    protected function casts(): array
    {
        return [
            'source_row' => 'integer',
            'source_values' => 'array',
        ];
    }

    /** @return BelongsTo<LegacyMigrationBatch, $this> */
    public function batch(): BelongsTo
    {
        return $this->belongsTo(LegacyMigrationBatch::class);
    }

    /** @return BelongsTo<LegacyMigrationCandidate, $this> */
    public function candidate(): BelongsTo
    {
        return $this->belongsTo(LegacyMigrationCandidate::class);
    }

    /** @return BelongsTo<LegacyMigrationOrphan, $this> */
    public function orphan(): BelongsTo
    {
        return $this->belongsTo(LegacyMigrationOrphan::class);
    }
}
