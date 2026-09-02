<?php

namespace App\Models;

use App\UnitStatus;
use App\UtilityStatus;
use Database\Factories\UnitFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

#[Fillable(['project_id', 'unit_code', 'block', 'number', 'status', 'building_progress', 'electricity_status', 'water_status'])]
class Unit extends Model
{
    /** @use HasFactory<UnitFactory> */
    use HasFactory;

    protected function casts(): array
    {
        return [
            'status' => UnitStatus::class,
            'building_progress' => 'integer',
            'electricity_status' => UtilityStatus::class,
            'water_status' => UtilityStatus::class,
        ];
    }

    /** @return BelongsTo<Project, $this> */
    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }

    /** @return HasMany<SalesCase, $this> */
    public function salesCases(): HasMany
    {
        return $this->hasMany(SalesCase::class);
    }

    /** @return HasOne<SalesCase, $this> */
    public function activeSalesCase(): HasOne
    {
        return $this->hasOne(SalesCase::class)->where('case_status', 'ACTIVE');
    }
}
