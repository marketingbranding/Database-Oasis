<?php

namespace App\Models;

use App\MigrationBatchStatus;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable(['branch_id', 'source_filename', 'source_fingerprint', 'audit_fingerprint', 'source_row_counts', 'status', 'created_by', 'completed_at'])]
class LegacyMigrationBatch extends Model
{
    protected function casts(): array
    {
        return [
            'source_row_counts' => 'array',
            'status' => MigrationBatchStatus::class,
            'completed_at' => 'datetime',
        ];
    }

    /** @return BelongsTo<Branch, $this> */
    public function branch(): BelongsTo
    {
        return $this->belongsTo(Branch::class);
    }

    /** @return BelongsTo<User, $this> */
    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /** @return HasMany<LegacyMigrationCandidate, $this> */
    public function candidates(): HasMany
    {
        return $this->hasMany(LegacyMigrationCandidate::class, 'batch_id');
    }

    /** @return HasMany<LegacyMigrationOrphan, $this> */
    public function orphans(): HasMany
    {
        return $this->hasMany(LegacyMigrationOrphan::class, 'batch_id');
    }

    /** @return HasMany<LegacyMigrationPlan, $this> */
    public function plans(): HasMany
    {
        return $this->hasMany(LegacyMigrationPlan::class, 'batch_id');
    }

    /** @return HasMany<LegacyMigrationProvenance, $this> */
    public function provenances(): HasMany
    {
        return $this->hasMany(LegacyMigrationProvenance::class, 'batch_id');
    }
}
