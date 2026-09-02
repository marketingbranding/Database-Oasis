<?php

namespace App;

use Filament\Support\Contracts\HasLabel;

enum UnitStatus: string implements HasLabel
{
    case Tersedia = 'TERSEDIA';
    case Booking = 'BOOKING';
    case Terjual = 'TERJUAL';

    public function getLabel(): string
    {
        return match ($this) {
            self::Tersedia => 'Tersedia',
            self::Booking => 'Booking',
            self::Terjual => 'Terjual',
        };
    }
}
