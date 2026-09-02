<?php

namespace App\Models;

use App\PsjbStatus;
use Database\Factories\PsjbFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

#[Fillable(['sales_case_id', 'psjb_date', 'document_number', 'coordinator_id', 'status', 'notes', 'created_by'])]
class Psjb extends Model
{
    /** @use HasFactory<PsjbFactory> */
    use HasFactory, SoftDeletes;

    protected function casts(): array
    {
        return [
            'psjb_date' => 'date',
            'status' => PsjbStatus::class,
        ];
    }

    /** @return BelongsTo<SalesCase, $this> */
    public function salesCase(): BelongsTo
    {
        return $this->belongsTo(SalesCase::class);
    }

    /** @return BelongsTo<User, $this> */
    public function coordinator(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /** @return BelongsTo<User, $this> */
    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /** @return HasMany<DocumentSubmission, $this> */
    public function documentSubmissions(): HasMany
    {
        return $this->hasMany(DocumentSubmission::class);
    }
}
