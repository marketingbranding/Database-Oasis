<?php

namespace App\Models;

use App\BastStatus;
use Database\Factories\BastRecordFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

#[Fillable(['sales_case_id', 'akad_id', 'bast_number', 'bast_date', 'status', 'notes', 'created_by'])]
class BastRecord extends Model
{
    /** @use HasFactory<BastRecordFactory> */
    use HasFactory, SoftDeletes;

    protected function casts(): array
    {
        return ['bast_date' => 'date', 'status' => BastStatus::class];
    }

    /** @return BelongsTo<SalesCase, $this> */
    public function salesCase(): BelongsTo
    {
        return $this->belongsTo(SalesCase::class);
    }

    /** @return BelongsTo<AkadRecord, $this> */
    public function akad(): BelongsTo
    {
        return $this->belongsTo(AkadRecord::class);
    }

    /** @return BelongsTo<User, $this> */
    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
