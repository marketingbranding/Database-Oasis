<?php

namespace App\Models;

use App\DocumentSubmissionStatus;
use Database\Factories\DocumentSubmissionFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;

#[Fillable(['sales_case_id', 'psjb_id', 'bank_id', 'submission_date', 'bank_branch', 'sequence', 'status', 'notes', 'created_by'])]
class DocumentSubmission extends Model
{
    /** @use HasFactory<DocumentSubmissionFactory> */
    use HasFactory, SoftDeletes;

    protected function casts(): array
    {
        return [
            'submission_date' => 'date',
            'sequence' => 'integer',
            'status' => DocumentSubmissionStatus::class,
        ];
    }

    /** @return BelongsTo<SalesCase, $this> */
    public function salesCase(): BelongsTo
    {
        return $this->belongsTo(SalesCase::class);
    }

    /** @return BelongsTo<Psjb, $this> */
    public function psjb(): BelongsTo
    {
        return $this->belongsTo(Psjb::class);
    }

    /** @return BelongsTo<Bank, $this> */
    public function bank(): BelongsTo
    {
        return $this->belongsTo(Bank::class);
    }

    /** @return BelongsTo<User, $this> */
    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /** @return HasMany<BankProcess, $this> */
    public function bankProcesses(): HasMany
    {
        return $this->hasMany(BankProcess::class);
    }

    /** @return HasOne<BankProcess, $this> */
    public function latestBankProcess(): HasOne
    {
        return $this->hasOne(BankProcess::class)->ofMany([
            'response_date' => 'max',
            'id' => 'max',
        ]);
    }
}
