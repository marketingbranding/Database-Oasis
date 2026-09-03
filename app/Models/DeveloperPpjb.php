<?php

namespace App\Models;

use App\DeveloperPpjbStatus;
use Database\Factories\DeveloperPpjbFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;

#[Fillable(['sales_case_id', 'bank_process_id', 'document_number', 'document_date', 'status', 'notes', 'created_by', 'is_legacy_import', 'legacy_date_missing'])]
class DeveloperPpjb extends Model
{
    /** @use HasFactory<DeveloperPpjbFactory> */
    use HasFactory, SoftDeletes;

    protected function casts(): array
    {
        return ['document_date' => 'date', 'status' => DeveloperPpjbStatus::class];
    }

    /** @return BelongsTo<SalesCase, $this> */
    public function salesCase(): BelongsTo
    {
        return $this->belongsTo(SalesCase::class);
    }

    /** @return BelongsTo<BankProcess, $this> */
    public function bankProcess(): BelongsTo
    {
        return $this->belongsTo(BankProcess::class);
    }

    /** @return BelongsTo<User, $this> */
    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /** @return HasOne<AkadRecord, $this> */
    public function akad(): HasOne
    {
        return $this->hasOne(AkadRecord::class);
    }
}
