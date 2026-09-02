<?php

namespace App;

use Filament\Support\Contracts\HasLabel;

enum BastStatus: string implements HasLabel
{
    case Completed = 'COMPLETED';
    case Cancelled = 'CANCELLED';

    public function getLabel(): string
    {
        return match ($this) {
            self::Completed => 'Completed',
            self::Cancelled => 'Cancelled',
        };
    }
}
