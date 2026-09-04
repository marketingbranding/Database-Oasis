<?php

namespace App\Services;

use App\Enums\LegacyResolutionType;

class LegacyResolutionCompatibilityService
{
    /**
     * @var array<string, array<int, LegacyResolutionType>>
     */
    private const MATRIX = [
        'CONSUMER_NIK_MISSING' => [LegacyResolutionType::MapConsumer, LegacyResolutionType::CorrectNik, LegacyResolutionType::ExcludeSourceRecord],
        'CONSUMER_NIK_INVALID' => [LegacyResolutionType::MapConsumer, LegacyResolutionType::CorrectNik, LegacyResolutionType::ExcludeSourceRecord],
        'CONSUMER_IDENTITY_AMBIGUOUS' => [LegacyResolutionType::MapConsumer, LegacyResolutionType::CorrectNik],
        'UNIT_NOT_FOUND' => [LegacyResolutionType::MapUnit, LegacyResolutionType::ExcludeSourceRecord],
        'UNIT_CODE_AMBIGUOUS' => [LegacyResolutionType::MapUnit, LegacyResolutionType::ResolveUnitConflict],
        'MULTIPLE_ACTIVE_UNIT_CANDIDATES' => [LegacyResolutionType::ResolveUnitConflict, LegacyResolutionType::ResolveLifecycle],
        'SALES_CASE_AMBIGUOUS' => [LegacyResolutionType::LinkSalesCase],
        'ORPHAN_BI' => [LegacyResolutionType::LinkOrphanRecord, LegacyResolutionType::ExcludeSourceRecord],
        'ORPHAN_PSJB' => [LegacyResolutionType::LinkOrphanRecord, LegacyResolutionType::ExcludeSourceRecord],
        'ORPHAN_SUBMISSION' => [LegacyResolutionType::LinkOrphanRecord, LegacyResolutionType::ExcludeSourceRecord],
        'ORPHAN_BANK_PROCESS' => [LegacyResolutionType::LinkOrphanRecord, LegacyResolutionType::MapBank, LegacyResolutionType::ExcludeSourceRecord],
        'BANK_NOT_FOUND' => [LegacyResolutionType::MapBank, LegacyResolutionType::ExcludeSourceRecord],
        'BANK_AMBIGUOUS' => [LegacyResolutionType::MapBank, LegacyResolutionType::ExcludeSourceRecord],
        'MULTIPLE_AUTHORITATIVE_APPROVAL_CANDIDATES' => [LegacyResolutionType::SelectAuthoritativeBankAttempt],
        'LIFECYCLE_CONFLICT' => [LegacyResolutionType::ResolveLifecycle],
        'MULTIPLE_AKAD' => [LegacyResolutionType::ResolveMultipleAkad, LegacyResolutionType::ExcludeSourceRecord],
        'MISSING_PROCESS_DATE' => [LegacyResolutionType::SupplyMissingDate, LegacyResolutionType::ExcludeSourceRecord],
        'INVALID_DATE' => [LegacyResolutionType::SupplyMissingDate, LegacyResolutionType::ExcludeSourceRecord],
        'MISSING_PROCESS_STATUS' => [LegacyResolutionType::AcceptUnknownStatus, LegacyResolutionType::SelectAuthoritativeBankAttempt, LegacyResolutionType::ExcludeSourceRecord],
        'UNKNOWN_STATUS_VALUE' => [LegacyResolutionType::AcceptUnknownStatus, LegacyResolutionType::ResolveLifecycle, LegacyResolutionType::ExcludeSourceRecord],
        'PLACEHOLDER_SP3K_VALUE' => [LegacyResolutionType::SelectAuthoritativeBankAttempt, LegacyResolutionType::ExcludeSourceRecord],
        'PPJB_WITHOUT_UPSTREAM' => [LegacyResolutionType::LinkSalesCase, LegacyResolutionType::ExcludeSourceRecord],
        'BAST_WITHOUT_AKAD' => [LegacyResolutionType::LinkSalesCase, LegacyResolutionType::ExcludeSourceRecord],
        'PREVIOUS_CASE_DEPENDENCY_NOT_READY' => [LegacyResolutionType::ExcludeSourceRecord],
    ];

    /** @return array<int, LegacyResolutionType> */
    public function allowedFor(string $exceptionCode): array
    {
        return self::MATRIX[$exceptionCode] ?? [LegacyResolutionType::ExcludeSourceRecord];
    }

    public function isCompatible(string $exceptionCode, LegacyResolutionType $type): bool
    {
        return in_array($type, $this->allowedFor($exceptionCode), true);
    }
}
