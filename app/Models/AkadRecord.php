<?php

namespace App\Models;

use Database\Factories\AkadRecordFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;

#[Fillable(['sales_case_id', 'developer_ppjb_id', 'document_number', 'akad_date', 'akad_quality', 'notes', 'created_by'])]
class AkadRecord extends Model
{
    /** @use HasFactory<AkadRecordFactory> */
    use HasFactory, SoftDeletes;

    protected function casts(): array
    {
        return ['akad_date' => 'date'];
    }

    /** @return BelongsTo<SalesCase, $this> */
    public function salesCase(): BelongsTo
    {
        return $this->belongsTo(SalesCase::class);
    }

    /** @return BelongsTo<DeveloperPpjb, $this> */
    public function developerPpjb(): BelongsTo
    {
        return $this->belongsTo(DeveloperPpjb::class);
    }

    /** @return BelongsTo<User, $this> */
    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /** @return HasOne<BastRecord, $this> */
    public function bast(): HasOne
    {
        return $this->hasOne(BastRecord::class, 'akad_id');
    }
}
