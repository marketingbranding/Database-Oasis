<?php

namespace App;

use Filament\Support\Contracts\HasLabel;

enum SalesCaseStatus: string implements HasLabel
{
    case Active = 'ACTIVE';
    case Completed = 'COMPLETED';
    case Mundur = 'MUNDUR';
    case Reject = 'REJECT';
    case PindahKavling = 'PINDAH_KAVLING';
    case Cancelled = 'CANCELLED';

    public function getLabel(): string
    {
        return match ($this) {
            self::Active => 'Active',
            self::Completed => 'Completed',
            self::Mundur => 'Mundur',
            self::Reject => 'Reject',
            self::PindahKavling => 'Pindah Kavling',
            self::Cancelled => 'Cancelled',
        };
    }
}
