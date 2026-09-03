<?php

namespace App;

use Filament\Support\Contracts\HasLabel;

enum DpStatus: string implements HasLabel
{
    case Unknown = 'UNKNOWN';
    case Complete = 'COMPLETE';
    case Incomplete = 'INCOMPLETE';

    public function getLabel(): string
    {
        return match ($this) {
            self::Unknown => 'Belum Diisi',
            self::Complete => 'Lengkap',
            self::Incomplete => 'Belum Lengkap',
        };
    }
}
