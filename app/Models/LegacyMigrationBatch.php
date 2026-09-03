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
}
