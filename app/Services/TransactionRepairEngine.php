<?php

namespace App\Services;

use App\Models\Branch;
use App\Models\RepairAction;
use App\Models\SalesCase;
use App\Models\User;
use App\Repairability;
use App\Services\Repair\MissingPpjbSystemCodeRule;
use App\Services\Repair\MissingSp3kSystemCodeRule;
use App\Services\Repair\RepairIssue;
use App\Services\Repair\RepairPlan;
use App\Services\Repair\RepairResult;
use App\Services\Repair\RepairRule;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

final class TransactionRepairEngine
{
    /** @var list<RepairRule> */
    private array $rules;

    public function __construct(
        MissingSp3kSystemCodeRule $sp3k,
        MissingPpjbSystemCodeRule $ppjb,
        private SalesCaseStageResolver $stages,
        private UnitStatusResolver $units,
    ) {
        $this->rules = [$sp3k, $ppjb];
    }

    /** @return list<RepairIssue> */
    public function scanBranch(Branch $branch): array
    {
        $issues = [];
        foreach ($this->rules as $rule) {
            array_push($issues, ...$rule->scan($branch));
        }
        usort($issues, fn (RepairIssue $a, RepairIssue $b): int => [$a->issueCode, $a->targetId] <=> [$b->issueCode, $b->targetId]);

        return $issues;
    }

    public function plan(RepairIssue $issue): RepairPlan
    {
        $rule = $this->ruleForIssue($issue);
        $target = $rule->lockTarget($issue->targetId);
        $current = $rule->detect($target);

        return new RepairPlan($current, $rule->actionCode(), $rule->before($target), $rule->proposed($target), $this->fingerprint($rule, $target, $current));
    }

    public function apply(User $user, RepairPlan $plan): RepairResult
    {
        return DB::transaction(function () use ($user, $plan): RepairResult {
            if ($plan->issue->repairability !== Repairability::AutoFixable) {
                throw ValidationException::withMessages(['repairability' => 'Only AUTO_FIXABLE repairs can be applied.']);
            }
            $rule = $this->ruleForIssue($plan->issue);
            if ($plan->actionCode !== $rule->actionCode()) {
                throw ValidationException::withMessages(['action_code' => 'Repair action does not match registered rule.']);
            }
            $case = SalesCase::query()->whereKey($plan->issue->salesCaseId)->lockForUpdate()->firstOrFail();
            if (! $user->can('update', $case)) {
                throw new AuthorizationException;
            }
            $target = $rule->lockTarget($plan->issue->targetId);
            if ($target->getAttribute('sales_case_id') !== $case->id) {
                throw ValidationException::withMessages(['target' => 'Repair target does not belong to SalesCase.']);
            }
            $current = $rule->detect($target);
            $fingerprint = $this->fingerprint($rule, $target, $current);
            if (! hash_equals($plan->fingerprint, $fingerprint)) {
                throw ValidationException::withMessages(['plan' => 'Repair plan is stale. Preview again.']);
            }
            $before = $rule->before($target);
            $stageBefore = $case->current_stage->value;
            $unitBefore = $case->unit?->status?->value;
            $code = $rule->apply($target);
            $this->stages->reconcile($case);
            $this->units->reconcile($case->unit_id);
            $rule->verify($target, $case, $before);
            $after = array_merge($before, [$current->issueCode === 'sp3k_system_code_missing' ? 'sp3k_code' : 'ppjb_code' => $code]);
            $audit = RepairAction::create(['branch_id' => $case->branch_id, 'sales_case_id' => $case->id, 'issue_code' => $current->issueCode, 'target_type' => $current->targetType, 'target_id' => $current->targetId, 'action_code' => $rule->actionCode(), 'repairability' => $current->repairability, 'plan_fingerprint' => $fingerprint, 'before_payload' => $before, 'after_payload' => $after, 'evidence_payload' => $current->evidence, 'performed_by' => $user->id]);

            return new RepairResult($plan, $audit->id, $before, $after, $stageBefore, $case->refresh()->current_stage->value, $unitBefore, $case->unit?->refresh()->status?->value, true);
        });
    }

    private function ruleForIssue(RepairIssue $issue): RepairRule
    {
        foreach ($this->rules as $rule) {
            if ($rule->issueCode() === $issue->issueCode && $rule->targetType() === $issue->targetType) {
                return $rule;
            }
        }
        throw ValidationException::withMessages(['rule' => 'Unknown repair rule or target type.']);
    }

    private function fingerprint(RepairRule $rule, Model $target, RepairIssue $issue): string
    {
        $payload = $rule->fingerprintPayload($target, $issue);
        ksort($payload);

        return hash('sha256', json_encode($payload, JSON_THROW_ON_ERROR));
    }
}
