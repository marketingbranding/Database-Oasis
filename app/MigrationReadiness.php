<?php

namespace App;

use Filament\Support\Contracts\HasLabel;

enum MigrationReadiness: string implements HasLabel
{
    case Auto = 'AUTO';
    case Review = 'REVIEW';
    case Blocked = 'BLOCKED';

    public function getLabel(): string
    {
        return match ($this) {
            self::Auto => 'Auto',
            self::Review => 'Review',
            self::Blocked => 'Blocked',
        };
    }
}
