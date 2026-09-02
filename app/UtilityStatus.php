<?php

namespace App;

use Filament\Support\Contracts\HasLabel;

enum UtilityStatus: string implements HasLabel
{
    case Terpasang = 'TERPASANG';
    case BelumTerpasang = 'BELUM_TERPASANG';

    public function getLabel(): string
    {
        return match ($this) {
            self::Terpasang => 'Terpasang',
            self::BelumTerpasang => 'Belum Terpasang',
        };
    }
}
