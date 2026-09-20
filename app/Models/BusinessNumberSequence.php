<?php

namespace App\Models;

use App\Enums\BusinessNumberType;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['type', 'branch_id', 'year', 'last_number'])]
class BusinessNumberSequence extends Model
{
    protected function casts(): array
    {
        return ['type' => BusinessNumberType::class, 'year' => 'integer', 'last_number' => 'integer'];
    }

    /** @return BelongsTo<Branch, $this> */
    public function branch(): BelongsTo
    {
        return $this->belongsTo(Branch::class);
    }
}
