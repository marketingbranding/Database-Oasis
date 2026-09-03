<?php

namespace App\LegacyMigration;

enum DuplicateClassification: string
{
    case ExactRowDuplicate = 'EXACT_ROW_DUPLICATE';
    case SameDocumentNumberDifferentCase = 'SAME_DOCUMENT_NUMBER_DIFFERENT_CASE';
    case Reissue = 'REISSUE';
    case MultipleBankAttempt = 'MULTIPLE_BANK_ATTEMPT';
    case ConflictingRecord = 'CONFLICTING_RECORD';
    case PossibleDataEntryDuplicate = 'POSSIBLE_DATA_ENTRY_DUPLICATE';
    case Unresolved = 'UNRESOLVED';
}
