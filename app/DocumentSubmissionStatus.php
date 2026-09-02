<?php

namespace App;

use Filament\Support\Contracts\HasLabel;

enum DocumentSubmissionStatus: string implements HasLabel
{
    case Submitted = 'SUBMITTED';
    case Processing = 'PROCESSING';
    case Closed = 'CLOSED';
    case Cancelled = 'CANCELLED';

    public function getLabel(): string
    {
        return match ($this) {
            self::Submitted => 'Submitted',
            self::Processing => 'Processing',
            self::Closed => 'Closed',
            self::Cancelled => 'Cancelled',
        };
    }
}
