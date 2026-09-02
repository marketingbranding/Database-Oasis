<?php

namespace App\Models;

use Database\Factories\ConsumerFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;

#[Fillable(['nik', 'name', 'phone', 'email', 'gender', 'birth_place', 'birth_date', 'address', 'occupation', 'company', 'monthly_income', 'marital_status', 'npwp', 'notes'])]
class Consumer extends Model
{
    /** @use HasFactory<ConsumerFactory> */
    use HasFactory, SoftDeletes;

    protected function casts(): array
    {
        return [
            'birth_date' => 'date',
            'monthly_income' => 'integer',
        ];
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
