<?php

namespace App;

use Filament\Support\Contracts\HasLabel;

enum DocumentSubmissionType: string implements HasLabel
{
    case Bank = 'BANK';
    case CashInternal = 'CASH_INTERNAL';

    public function getLabel(): string
    {
        return match ($this) {
            self::Bank => 'Pemberkasan Bank',
            self::CashInternal => 'Pemberkasan CASH',
        };
    }
}
