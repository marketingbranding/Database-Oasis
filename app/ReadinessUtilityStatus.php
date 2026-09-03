<?php

namespace App;

use Filament\Support\Contracts\HasLabel;

enum ReadinessUtilityStatus: string implements HasLabel
{
    case Unknown = 'UNKNOWN';
    case Installed = 'INSTALLED';
    case NotInstalled = 'NOT_INSTALLED';

    public function getLabel(): string
    {
        return match ($this) {
            self::Unknown => 'Belum Diisi',
            self::Installed => 'Terpasang',
            self::NotInstalled => 'Belum Terpasang',
        };
    }
}
