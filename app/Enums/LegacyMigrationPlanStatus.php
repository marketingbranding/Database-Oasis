<?php

namespace App\Enums;

use Filament\Support\Contracts\HasLabel;

enum LegacyMigrationPlanStatus: string implements HasLabel
{
    case Generated = 'GENERATED';
    case Approved = 'APPROVED';
    case Stale = 'STALE';
    case Simulated = 'SIMULATED';
    case Failed = 'FAILED';

    public function getLabel(): string
    {
        return match ($this) {
            self::Generated => 'Generated',
            self::Approved => 'Approved',
            self::Stale => 'Stale',
            self::Simulated => 'Simulated',
            self::Failed => 'Failed',
        };
    }
}
