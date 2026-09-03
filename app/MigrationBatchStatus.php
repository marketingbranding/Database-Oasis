<?php

namespace App;

use Filament\Support\Contracts\HasLabel;

enum MigrationBatchStatus: string implements HasLabel
{
    case Audited = 'AUDITED';
    case Reviewing = 'REVIEWING';
    case ReadyForDryRun = 'READY_FOR_DRY_RUN';
    case DryRunComplete = 'DRY_RUN_COMPLETE';
    case Superseded = 'SUPERSEDED';

    public function getLabel(): string
    {
        return match ($this) {
            self::Audited => 'Audited',
            self::Reviewing => 'Reviewing',
            self::ReadyForDryRun => 'Ready for Dry Run',
            self::DryRunComplete => 'Dry Run Complete',
            self::Superseded => 'Superseded',
        };
    }
}
