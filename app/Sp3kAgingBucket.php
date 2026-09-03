<?php

namespace App;

use Filament\Support\Contracts\HasLabel;

enum Sp3kAgingBucket: string implements HasLabel
{
    case ZeroToSeven = '0_7';
    case EightToFourteen = '8_14';
    case FifteenToThirty = '15_30';
    case OverThirty = 'OVER_30';

    public function getLabel(): string
    {
        return match ($this) {
            self::ZeroToSeven => '0–7 hari',
            self::EightToFourteen => '8–14 hari',
            self::FifteenToThirty => '15–30 hari',
            self::OverThirty => '>30 hari',
        };
    }
}
