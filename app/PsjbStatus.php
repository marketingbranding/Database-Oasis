<?php

namespace App;

use Filament\Support\Contracts\HasLabel;

enum PsjbStatus: string implements HasLabel
{
    case Active = 'ACTIVE';
    case Superseded = 'SUPERSEDED';
    case Cancelled = 'CANCELLED';

    public function getLabel(): string
    {
        return match ($this) {
            self::Active => 'Active',
            self::Superseded => 'Superseded',
            self::Cancelled => 'Cancelled',
        };
    }
}
