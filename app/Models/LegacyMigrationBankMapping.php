<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['batch_id', 'raw_legacy_value', 'normalized_alias', 'target_bank_id', 'approved_by', 'approved_at', 'reason', 'source_fingerprint', 'audit_fingerprint'])]
class LegacyMigrationBankMapping extends Model
{
    protected function casts(): array
    {
        return ['approved_at' => 'datetime'];
    }

    /** @return BelongsTo<LegacyMigrationBatch, $this> */
    public function batch(): BelongsTo
    {
        return $this->belongsTo(LegacyMigrationBatch::class, 'batch_id');
    }

    /** @return BelongsTo<Bank, $this> */
    public function bank(): BelongsTo
    {
        return $this->belongsTo(Bank::class, 'target_bank_id');
    }

    /** @return BelongsTo<User, $this> */
    public function approver(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by');
    }
}
