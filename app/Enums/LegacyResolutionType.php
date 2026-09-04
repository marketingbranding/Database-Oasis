<?php

namespace App\Enums;

use Filament\Support\Contracts\HasLabel;

enum LegacyResolutionType: string implements HasLabel
{
    case MapConsumer = 'MAP_CONSUMER';
    case CorrectNik = 'CORRECT_NIK';
    case MapUnit = 'MAP_UNIT';
    case MapBank = 'MAP_BANK';
    case ResolveUnitConflict = 'RESOLVE_UNIT_CONFLICT';
    case LinkSalesCase = 'LINK_SALES_CASE';
    case LinkOrphanRecord = 'LINK_ORPHAN_RECORD';
    case SelectAuthoritativeBankAttempt = 'SELECT_AUTHORITATIVE_BANK_ATTEMPT';
    case ResolveLifecycle = 'RESOLVE_LIFECYCLE';
    case ResolveMultipleAkad = 'RESOLVE_MULTIPLE_AKAD';
    case SupplyMissingDate = 'SUPPLY_MISSING_DATE';
    case AcceptUnknownStatus = 'ACCEPT_UNKNOWN_STATUS';
    case ExcludeSourceRecord = 'EXCLUDE_SOURCE_RECORD';

    public function getLabel(): string
    {
        return match ($this) {
            self::MapConsumer => 'Map Consumer',
            self::CorrectNik => 'Correct NIK',
            self::MapUnit => 'Map Unit',
            self::MapBank => 'Map Bank',
            self::ResolveUnitConflict => 'Resolve Unit Conflict',
            self::LinkSalesCase => 'Link Sales Case',
            self::LinkOrphanRecord => 'Link Orphan Record',
            self::SelectAuthoritativeBankAttempt => 'Select Authoritative Bank Attempt',
            self::ResolveLifecycle => 'Resolve Lifecycle',
            self::ResolveMultipleAkad => 'Resolve Multiple Akad',
            self::SupplyMissingDate => 'Supply Missing Date',
            self::AcceptUnknownStatus => 'Accept Unknown Status',
            self::ExcludeSourceRecord => 'Exclude Source Record',
        };
    }
}
