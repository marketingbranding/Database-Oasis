<?php

namespace App\Enums;

use Filament\Support\Contracts\HasLabel;

enum LegacyMigrationPlanOperationType: string implements HasLabel
{
    case CreateConsumer = 'CREATE_CONSUMER';
    case ReuseConsumer = 'REUSE_CONSUMER';
    case MatchUnit = 'MATCH_UNIT';
    case CreateSalesCase = 'CREATE_SALES_CASE';
    case LinkPreviousCase = 'LINK_PREVIOUS_CASE';
    case CreateBiCheck = 'CREATE_BI_CHECK';
    case CreatePsjb = 'CREATE_PSJB';
    case CreateDocumentSubmission = 'CREATE_DOCUMENT_SUBMISSION';
    case CreateBankProcess = 'CREATE_BANK_PROCESS';
    case CreateDeveloperPpjb = 'CREATE_DEVELOPER_PPJB';
    case CreateAkad = 'CREATE_AKAD';
    case CreateBast = 'CREATE_BAST';
    case SetFinalLifecycle = 'SET_FINAL_LIFECYCLE';
    case SetFinalUnitState = 'SET_FINAL_UNIT_STATE';

    public function getLabel(): string
    {
        return match ($this) {
            self::CreateConsumer => 'Create Consumer',
            self::ReuseConsumer => 'Reuse Consumer',
            self::MatchUnit => 'Match Unit',
            self::CreateSalesCase => 'Create Sales Case',
            self::LinkPreviousCase => 'Link Previous Case',
            self::CreateBiCheck => 'Create BI Check',
            self::CreatePsjb => 'Create PSJB',
            self::CreateDocumentSubmission => 'Create Document Submission',
            self::CreateBankProcess => 'Create Bank Process',
            self::CreateDeveloperPpjb => 'Create Developer PPJB',
            self::CreateAkad => 'Create Akad',
            self::CreateBast => 'Create BAST',
            self::SetFinalLifecycle => 'Set Final Lifecycle',
            self::SetFinalUnitState => 'Set Final Unit State',
        };
    }
}
