<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['candidate_id', 'exception_code', 'resolution_type', 'selected_value', 'note', 'resolved_by', 'resolved_at', 'source_fingerprint', 'audit_fingerprint'])]
class LegacyMigrationResolution extends Model
{
    protected function casts(): array
    {
        return [
            'selected_value' => 'array',
            'resolved_at' => 'datetime',
        ];
    }

    /** @return BelongsTo<LegacyMigrationCandidate, $this> */
    public function candidate(): BelongsTo
    {
        return $this->belongsTo(LegacyMigrationCandidate::class);
    }

    /** @return BelongsTo<User, $this> */
    public function resolver(): BelongsTo
    {
        return $this->belongsTo(User::class, 'resolved_by');
    }
}
