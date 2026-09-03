<?php

namespace App\Enums;

use Filament\Support\Contracts\HasLabel;

enum LegacyOrphanDecision: string implements HasLabel
{
    case LinkToCandidate = 'LINK_TO_CANDIDATE';
    case ExcludeAsDuplicate = 'EXCLUDE_AS_DUPLICATE';
    case ExcludeAsIrrelevant = 'EXCLUDE_AS_IRRELEVANT';
    case LeaveUnresolved = 'LEAVE_UNRESOLVED';

    public function getLabel(): string
    {
        return match ($this) {
            self::LinkToCandidate => 'Link to Candidate',
            self::ExcludeAsDuplicate => 'Exclude as Duplicate',
            self::ExcludeAsIrrelevant => 'Exclude as Irrelevant',
            self::LeaveUnresolved => 'Leave Unresolved',
        };
    }
}
