<?php

namespace App;

use Filament\Support\Contracts\HasLabel;

enum MigrationReviewDecision: string implements HasLabel
{
    case Accept = 'ACCEPT';
    case Reject = 'REJECT';
    case NeedsCorrection = 'NEEDS_CORRECTION';

    public function getLabel(): string
    {
        return match ($this) {
            self::Accept => 'Accept',
            self::Reject => 'Reject',
            self::NeedsCorrection => 'Needs Correction',
        };
    }
}
