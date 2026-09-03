<?php

namespace App\Enums;

use Filament\Support\Contracts\HasLabel;

enum LegacyOrphanStatus: string implements HasLabel
{
    case Pending = 'PENDING';
    case Linked = 'LINKED';
    case Excluded = 'EXCLUDED';
    case Unresolved = 'UNRESOLVED';

    public function getLabel(): string
    {
        return match ($this) {
            self::Pending => 'Pending',
            self::Linked => 'Linked',
            self::Excluded => 'Excluded',
            self::Unresolved => 'Unresolved',
        };
    }
}
