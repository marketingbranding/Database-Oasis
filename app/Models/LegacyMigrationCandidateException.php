<?php

namespace App\Models;

use App\Enums\MigrationExceptionSeverity;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['candidate_id', 'code', 'severity', 'source_sheet', 'source_row', 'entity_type', 'message', 'evidence'])]
class LegacyMigrationCandidateException extends Model
{
    protected function casts(): array
    {
        return [
            'severity' => MigrationExceptionSeverity::class,
            'source_row' => 'integer',
            'evidence' => 'array',
        ];
    }

    /** @return BelongsTo<LegacyMigrationCandidate, $this> */
    public function candidate(): BelongsTo
    {
        return $this->belongsTo(LegacyMigrationCandidate::class);
    }
}
