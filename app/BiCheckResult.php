<?php

namespace App;

use Filament\Support\Contracts\HasLabel;

enum BiCheckResult: string implements HasLabel
{
    case Clear = 'CLEAR';
    case Review = 'REVIEW';
    case Rejected = 'REJECTED';

    public function getLabel(): string
    {
        return match ($this) {
            self::Clear => 'Clear',
            self::Review => 'Review',
            self::Rejected => 'Rejected',
        };
    }
}
