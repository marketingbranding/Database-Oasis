<?php

namespace App\Services;

use App\Models\BankProcess;
use App\Models\Branch;
use App\Models\DeveloperPpjb;
use App\Models\RepairAction;
use App\Models\SalesCase;
use App\Models\User;
use App\Repairability;
use App\Services\Repair\RepairIssue;
use App\Services\Repair\RepairPlan;
use App\Services\Repair\RepairResult;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

final class TransactionRepairEngine
{
    public function __construct(
        private BusinessNumberGenerator $numbers,
        private SalesCaseStageResolver $stages,
        private UnitStatusResolver $units,
    ) {}

    /** @return list<RepairIssue> */
    public function scanBranch(Branch $branch): array
    {
        $issues = [];
        BankProcess::query()->whereHas('salesCase', fn ($q) => $q->where('branch_id', $branch->id))->where('is_authoritative', true)->whereNotNull('sp3k_date')->whereNull('sp3k_code')->each(function (BankProcess $process) use (&$issues): void {
            $issues[] = $this->sp3kIssue($process);
        });
        DeveloperPpjb::query()->whereHas('salesCase', fn ($q) => $q->where('branch_id', $branch->id))->whereNull('ppjb_code')->each(function (DeveloperPpjb $ppjb) use (&$issues): void {
            $issues[] = $this->ppjbIssue($ppjb);
        });
        usort($issues, fn (RepairIssue $a, RepairIssue $b): int => [$a->issueCode, $a->targetId] <=> [$b->issueCode, $b->targetId]);

        return $issues;
    }

    public function plan(RepairIssue $issue): RepairPlan
    {
        $current = $this->redetect($issue);
        $before = $issue->issueCode === 'sp3k_system_code_missing' ? ['sp3k_code' => null] : ['ppjb_code' => null];
        $prefix = $issue->issueCode === 'sp3k_system_code_missing' ? 'SP3K' : 'PPJB';

        return new RepairPlan($current, 'GENERATE_SYSTEM_CODE', $before, ['system_code' => "{$prefix}-SYSTEM-GENERATED"], $this->fingerprint($current));
    }

    public function apply(User $user, RepairPlan $plan): RepairResult
    {
        return DB::transaction(function () use ($user, $plan): RepairResult {
            $case = SalesCase::query()->whereKey($plan->issue->salesCaseId)->lockForUpdate()->firstOrFail();
            if (! $user->can('update', $case)) {
                throw new AuthorizationException;
            }
            $target = $plan->issue->targetType === BankProcess::class
                ? BankProcess::query()->whereKey($plan->issue->targetId)->lockForUpdate()->firstOrFail()
                : DeveloperPpjb::query()->whereKey($plan->issue->targetId)->lockForUpdate()->firstOrFail();
            $current = $target instanceof BankProcess ? $this->sp3kIssue($target) : $this->ppjbIssue($target);
            if (! hash_equals($plan->fingerprint, $this->fingerprint($current))) {
                throw ValidationException::withMessages(['plan' => 'Repair plan is stale. Preview again.']);
            }
            $code = $target instanceof BankProcess ? $this->numbers->ensureSp3kCode($target) : $this->numbers->ensurePpjbCode($target);
            $this->stages->reconcile($case);
            $this->units->reconcile($case->unit_id);
            $after = [$target instanceof BankProcess ? 'sp3k_code' : 'ppjb_code' => $code];
            $audit = RepairAction::create([
                'branch_id' => $case->branch_id, 'sales_case_id' => $case->id,
                'issue_code' => $current->issueCode, 'target_type' => $current->targetType, 'target_id' => $current->targetId,
                'action_code' => $plan->actionCode, 'repairability' => Repairability::AutoFixable,
                'plan_fingerprint' => $plan->fingerprint, 'before_payload' => $plan->before,
                'after_payload' => $after, 'evidence_payload' => $current->evidence, 'performed_by' => $user->id,
            ]);

            return new RepairResult($plan, $audit->id, $after);
        });
    }

    private function redetect(RepairIssue $issue): RepairIssue
    {
        $target = $issue->targetType === BankProcess::class ? BankProcess::findOrFail($issue->targetId) : DeveloperPpjb::findOrFail($issue->targetId);

        return $target instanceof BankProcess ? $this->sp3kIssue($target) : $this->ppjbIssue($target);
    }

    private function sp3kIssue(BankProcess $process): RepairIssue
    {
        if (! $process->is_authoritative || $process->sp3k_date === null || $process->sp3k_code !== null) {
            throw ValidationException::withMessages(['issue' => 'SP3K issue no longer exists.']);
        }

        return new RepairIssue('sp3k_system_code_missing', $process->sales_case_id, $process->salesCase->branch_id, BankProcess::class, $process->id, Repairability::AutoFixable, 'Canonical SP3K code is missing.', ['is_authoritative' => true, 'sp3k_date' => $process->sp3k_date->toDateString()]);
    }

    private function ppjbIssue(DeveloperPpjb $ppjb): RepairIssue
    {
        if ($ppjb->ppjb_code !== null) {
            throw ValidationException::withMessages(['issue' => 'PPJB issue no longer exists.']);
        }

        return new RepairIssue('ppjb_system_code_missing', $ppjb->sales_case_id, $ppjb->salesCase->branch_id, DeveloperPpjb::class, $ppjb->id, Repairability::AutoFixable, 'Canonical PPJB code is missing.', ['document_date' => $ppjb->document_date->toDateString(), 'status' => $ppjb->status->value]);
    }

    private function fingerprint(RepairIssue $issue): string
    {
        return hash('sha256', json_encode($issue->toArray(), JSON_THROW_ON_ERROR));
    }
}
