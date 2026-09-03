<?php

namespace App\Models;

use Carbon\CarbonImmutable;
use Carbon\CarbonInterface;
use Database\Factories\AkadTargetFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['branch_id', 'project_id', 'period_month', 'target', 'created_by', 'updated_by'])]
class AkadTarget extends Model
{
    /** @use HasFactory<AkadTargetFactory> */
    use HasFactory;

    protected function casts(): array
    {
        return [
            'period_month' => 'date',
            'target' => 'integer',
        ];
    }

    public function setPeriodMonthAttribute(CarbonInterface|string $value): void
    {
        $this->attributes['period_month'] = CarbonImmutable::parse($value)->startOfMonth()->toDateString();
    }

    /** @return BelongsTo<Branch, $this> */
    public function branch(): BelongsTo
    {
        return $this->belongsTo(Branch::class);
    }

    /** @return BelongsTo<Project, $this> */
    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }

    /** @return BelongsTo<User, $this> */
    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /** @return BelongsTo<User, $this> */
    public function updatedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'updated_by');
    }
}
