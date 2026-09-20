<?php

namespace App\Services;

use App\BiCheckResult;
use App\DeveloperPpjbStatus;
use App\DocumentSubmissionStatus;
use App\DocumentSubmissionType;
use App\FinancingType;
use App\Models\SalesCase;
use App\PsjbStatus;
use App\SalesCaseStage;

final class SalesCaseStageResolver
{
    public function resolve(SalesCase $case): SalesCaseStage
    {
        if ($case->bast()->exists()) {
            return SalesCaseStage::Completed;
        }

        if ($case->akad()->exists()) {
            return SalesCaseStage::Bast;
        }

        if ($case->developerPpjbs()->where('status', DeveloperPpjbStatus::Active->value)->exists()) {
            return SalesCaseStage::Akad;
        }

        if ($case->financing_type === FinancingType::Cash) {
            if ($case->documentSubmissions()
                ->where('type', DocumentSubmissionType::CashInternal->value)
                ->where('status', '!=', DocumentSubmissionStatus::Cancelled->value)
                ->exists()) {
                return SalesCaseStage::PpjbDev;
            }

            return $case->psjbs()->where('status', PsjbStatus::Active->value)->exists()
                ? SalesCaseStage::Pemberkasan
                : SalesCaseStage::Psjb;
        }

        if ($case->bankProcesses()
            ->where('is_authoritative', true)
            ->whereNotNull('sp3k_number')
            ->whereNotNull('sp3k_date')
            ->exists()) {
            return SalesCaseStage::PpjbDev;
        }

        if ($case->bankProcesses()->exists()) {
            return SalesCaseStage::ProsesBank;
        }

        if ($case->documentSubmissions()
            ->where('status', '!=', DocumentSubmissionStatus::Cancelled->value)
            ->exists()) {
            return SalesCaseStage::ProsesBank;
        }

        if ($case->psjbs()->where('status', PsjbStatus::Active->value)->exists()) {
            return SalesCaseStage::Pemberkasan;
        }

        return $case->latestBiCheck()->first()?->result === BiCheckResult::Clear
            ? SalesCaseStage::Psjb
            : SalesCaseStage::BiChecking;
    }

    public function reconcile(SalesCase $case): SalesCase
    {
        $case->update(['current_stage' => $this->resolve($case)]);

        return $case->refresh();
    }
}
