<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['plan_id', 'plan_fingerprint', 'status', 'started_by', 'environment', 'database_connection', 'database_name', 'backup_reference', 'backup_created_at', 'started_at', 'completed_at', 'preflight_summary', 'result_summary', 'failure_reason'])]
class LegacyMigrationExecution extends Model
{
    protected function casts(): array
    {
        return [
            'backup_created_at' => 'datetime',
            'started_at' => 'datetime',
            'completed_at' => 'datetime',
            'preflight_summary' => 'array',
            'result_summary' => 'array',
        ];
    }

    /** @return BelongsTo<LegacyMigrationPlan, $this> */
    public function plan(): BelongsTo
    {
        return $this->belongsTo(LegacyMigrationPlan::class, 'plan_id');
    }

    /** @return BelongsTo<User, $this> */
    public function startedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'started_by');
    }
}
