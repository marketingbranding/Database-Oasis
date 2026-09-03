<?php

namespace App;

use Filament\Support\Contracts\HasLabel;

enum ReadinessIssueStatus: string implements HasLabel
{
    case Unknown = 'UNKNOWN';
    case Clear = 'CLEAR';
    case Issue = 'ISSUE';

    public function getLabel(): string
    {
        return match ($this) {
            self::Unknown => 'Belum Diisi',
            self::Clear => 'Clear',
            self::Issue => 'Ada Kendala',
        };
    }
}
