<?php

namespace App\Models;

use App\BankResponseType;
use Database\Factories\BankProcessFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

#[Fillable(['sales_case_id', 'document_submission_id', 'bank_id', 'response_type', 'response_date', 'sp3k_number', 'sp3k_date', 'credit_limit', 'tenor', 'is_authoritative', 'notes', 'created_by', 'is_legacy_import', 'legacy_date_missing'])]
class BankProcess extends Model
{
    /** @use HasFactory<BankProcessFactory> */
    use HasFactory, SoftDeletes;

    protected function casts(): array
    {
        return [
            'response_type' => BankResponseType::class,
            'response_date' => 'date',
            'sp3k_date' => 'date',
            'credit_limit' => 'integer',
            'tenor' => 'integer',
            'is_authoritative' => 'boolean',
        ];
    }

    public static function latestForSubmission(string $submissionId): ?self
    {
        return self::query()
            ->where('document_submission_id', $submissionId)
            ->orderByDesc('response_date')
            ->orderByDesc('id')
            ->first();
    }

    /** @return BelongsTo<SalesCase, $this> */
    public function salesCase(): BelongsTo
    {
        return $this->belongsTo(SalesCase::class);
    }

    /** @return BelongsTo<DocumentSubmission, $this> */
    public function documentSubmission(): BelongsTo
    {
        return $this->belongsTo(DocumentSubmission::class);
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
}
