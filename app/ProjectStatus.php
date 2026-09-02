<?php

namespace App;

use Filament\Support\Contracts\HasLabel;

enum ProjectStatus: string implements HasLabel
{
    case Aktif = 'AKTIF';
    case Nonaktif = 'NONAKTIF';

    public function getLabel(): string
    {
        return match ($this) {
            self::Aktif => 'Aktif',
            self::Nonaktif => 'Nonaktif',
        };
    }
}
