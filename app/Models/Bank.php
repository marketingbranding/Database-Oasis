<?php

namespace App\Models;

use Database\Factories\BankFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;

#[Fillable(['code', 'name', 'is_active'])]
class Bank extends Model
{
    /** @use HasFactory<BankFactory> */
    use HasFactory;

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
        ];
    }
}
