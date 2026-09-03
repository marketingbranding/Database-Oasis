<?php

namespace App\Models;

use App\Enums\LegacyMigrationPlanStatus;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable(['batch_id', 'status', 'source_fingerprint', 'audit_fingerprint', 'candidate_state_fingerprint', 'review_resolution_fingerprint', 'plan_fingerprint', 'summary_totals', 'simulation_result', 'generated_by', 'generated_at'])]
class LegacyMigrationPlan extends Model
{
    protected function casts(): array
    {
        return [
            'status' => LegacyMigrationPlanStatus::class,
            'summary_totals' => 'array',
            'simulation_result' => 'array',
            'generated_at' => 'datetime',
        ];
    }

    /** @return BelongsTo<LegacyMigrationBatch, $this> */
    public function batch(): BelongsTo
    {
        return $this->belongsTo(LegacyMigrationBatch::class);
    }

    /** @return BelongsTo<User, $this> */
    public function generatedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'generated_by');
    }

    /** @return HasMany<LegacyMigrationPlanOperation, $this> */
    public function operations(): HasMany
    {
        return $this->hasMany(LegacyMigrationPlanOperation::class, 'plan_id');
    }
}
