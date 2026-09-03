<?php

namespace App\LegacyMigration;

enum AuditExceptionCode: string
{
    case ConsumerNikMissing = 'CONSUMER_NIK_MISSING';
    case ConsumerNikInvalid = 'CONSUMER_NIK_INVALID';
    case ConsumerIdentityAmbiguous = 'CONSUMER_IDENTITY_AMBIGUOUS';
    case UnitNotFound = 'UNIT_NOT_FOUND';
    case UnitCodeAmbiguous = 'UNIT_CODE_AMBIGUOUS';
    case MultipleActiveUnitCandidates = 'MULTIPLE_ACTIVE_UNIT_CANDIDATES';
    case SalesCaseAmbiguous = 'SALES_CASE_AMBIGUOUS';
    case PotentialPindahKavling = 'POTENTIAL_PINDAH_KAVLING';
    case OrphanBi = 'ORPHAN_BI';
    case OrphanPsjb = 'ORPHAN_PSJB';
    case OrphanSubmission = 'ORPHAN_SUBMISSION';
    case OrphanBankProcess = 'ORPHAN_BANK_PROCESS';
    case MultipleAuthoritativeApprovalCandidates = 'MULTIPLE_AUTHORITATIVE_APPROVAL_CANDIDATES';
    case CashFakeSp3k = 'CASH_FAKE_SP3K';
    case PpjbWithoutUpstream = 'PPJB_WITHOUT_UPSTREAM';
    case MultipleAkad = 'MULTIPLE_AKAD';
    case BastWithoutAkad = 'BAST_WITHOUT_AKAD';
    case ChronologyViolation = 'CHRONOLOGY_VIOLATION';
    case DuplicateDocumentNumber = 'DUPLICATE_DOCUMENT_NUMBER';
    case UnknownStatusValue = 'UNKNOWN_STATUS_VALUE';
    case InvalidDate = 'INVALID_DATE';
    case ExactRowDuplicate = 'EXACT_ROW_DUPLICATE';
    case MissingRequiredColumn = 'MISSING_REQUIRED_COLUMN';
}
