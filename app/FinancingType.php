<?php

namespace App;

use Filament\Support\Contracts\HasLabel;

enum FinancingType: string implements HasLabel
{
    case KprSubsidi = 'KPR_SUBSIDI';
    case Cash = 'CASH';

    public function getLabel(): string
    {
        return match ($this) {
            self::KprSubsidi => 'KPR Subsidi',
            self::Cash => 'CASH',
        };
    }
}
