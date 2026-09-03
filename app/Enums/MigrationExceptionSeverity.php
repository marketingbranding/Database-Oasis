<?php

namespace App\Enums;

use Filament\Support\Contracts\HasLabel;

enum MigrationExceptionSeverity: string implements HasLabel
{
    case Warning = 'WARNING';
    case Review = 'REVIEW';
    case Blocking = 'BLOCKING';

    public function getLabel(): string
    {
        return match ($this) {
            self::Warning => 'Warning',
            self::Review => 'Review',
            self::Blocking => 'Blocking',
        };
    }
}
