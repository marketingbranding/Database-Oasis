<?php

namespace App\Models;

use App\Enums\LegacyMigrationPlanOperationType;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['plan_id', 'candidate_id', 'orphan_id', 'operation_type', 'payload', 'sequence', 'error'])]
class LegacyMigrationPlanOperation extends Model
{
    protected function casts(): array
    {
        return [
            'operation_type' => LegacyMigrationPlanOperationType::class,
            'payload' => 'array',
            'sequence' => 'integer',
        ];
    }

    /** @return BelongsTo<LegacyMigrationPlan, $this> */
    public function plan(): BelongsTo
    {
        return $this->belongsTo(LegacyMigrationPlan::class);
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
