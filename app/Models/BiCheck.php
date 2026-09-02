<?php

namespace App\Models;

use App\BiCheckResult;
use Database\Factories\BiCheckFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

#[Fillable(['sales_case_id', 'check_date', 'result', 'description', 'created_by'])]
class BiCheck extends Model
{
    /** @use HasFactory<BiCheckFactory> */
    use HasFactory, SoftDeletes;

    protected function casts(): array
    {
        return [
            'check_date' => 'date',
            'result' => BiCheckResult::class,
        ];
    }

    /**
     * Latest non-deleted BI check for a sales case. Central query for the
     * "current BI result" — do not duplicate it in Filament pages.
     */
    public static function latestForCase(string $salesCaseId): ?self
    {
        return self::query()
            ->where('sales_case_id', $salesCaseId)
            ->orderByDesc('check_date')
            ->orderByDesc('id')
            ->first();
    }

    /** @return BelongsTo<SalesCase, $this> */
    public function salesCase(): BelongsTo
    {
        return $this->belongsTo(SalesCase::class);
    }

    /** @return BelongsTo<User, $this> */
    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
