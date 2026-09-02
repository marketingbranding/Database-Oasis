<?php

namespace App;

use Filament\Support\Contracts\HasLabel;

enum BankResponseType: string implements HasLabel
{
    case Process = 'PROCESS';
    case Revision = 'REVISION';
    case Approved = 'APPROVED';
    case Rejected = 'REJECTED';

    public function getLabel(): string
    {
        return match ($this) {
            self::Process => 'Process',
            self::Revision => 'Revision',
            self::Approved => 'Approved',
            self::Rejected => 'Rejected',
        };
    }
}
