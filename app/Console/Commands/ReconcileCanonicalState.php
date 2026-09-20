<?php

namespace App\Console\Commands;

use App\Models\Branch;
use App\Models\SalesCase;
use App\Models\Unit;
use App\Services\SalesCaseStageResolver;
use App\Services\UnitStatusResolver;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;

#[Signature('oasis:reconcile-canonical-state {--branch-id= : Reconcile one branch only} {--apply : Persist derived-state changes} {--json : Emit machine-readable summary}')]
#[Description('Inspect or apply canonical SalesCase stages and Unit statuses for one branch.')]
class ReconcileCanonicalState extends Command
{
    public function handle(SalesCaseStageResolver $stageResolver, UnitStatusResolver $unitResolver): int
    {
        $branchId = $this->option('branch-id');
        $json = (bool) $this->option('json');

        if (! is_string($branchId) || $branchId === '') {
            return $this->failCommand($json, 'branch_id is required');
        }

        $branch = Branch::find($branchId);

        if ($branch === null) {
            return $this->failCommand($json, 'Branch not found: '.$branchId);
        }

        $apply = (bool) $this->option('apply');
        $summary = $this->scan($branch, $stageResolver, $unitResolver, $apply);

        if ($json) {
            $this->line(json_encode($summary, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));
        } else {
            $this->renderSummary($summary);
        }

        return self::SUCCESS;
    }

    /** @return array<string, mixed> */
    private function scan(Branch $branch, SalesCaseStageResolver $stageResolver, UnitStatusResolver $unitResolver, bool $apply): array
    {
        $summary = [
            'mode' => $apply ? 'APPLY' : 'DRY RUN',
            'branch' => ['id' => $branch->id, 'name' => $branch->name],
            'sales_cases' => ['scanned' => 0, 'canonical' => 0, 'changes_required' => 0, 'changes_applied' => 0],
            'units' => ['scanned' => 0, 'canonical' => 0, 'changes_required' => 0, 'changes_applied' => 0],
            'stage_transitions' => [],
            'unit_status_transitions' => [],
            'anomalies' => ['total' => 0, 'counts' => [], 'items' => []],
        ];

        DB::transaction(function () use (&$summary, $branch, $stageResolver, $unitResolver, $apply): void {
            SalesCase::query()->where('branch_id', $branch->id)->with(['project', 'unit'])->each(function (SalesCase $case) use (&$summary, $stageResolver, $apply): void {
                $summary['sales_cases']['scanned']++;
                $expected = $stageResolver->resolve($case);
                $actual = $case->current_stage;
                if ($actual === $expected) {
                    $summary['sales_cases']['canonical']++;
                } else {
                    $summary['sales_cases']['changes_required']++;
                    $key = $actual->value.' -> '.$expected->value;
                    $summary['stage_transitions'][$key] = ($summary['stage_transitions'][$key] ?? 0) + 1;
                    if ($apply) {
                        $case->update(['current_stage' => $expected]);
                        $summary['sales_cases']['changes_applied']++;
                    }
                }
                $this->detectAnomalies($case, $summary);
            });

            Unit::query()->whereHas('project', fn (Builder $query): Builder => $query->where('branch_id', $branch->id))->each(function (Unit $unit) use (&$summary, $unitResolver, $apply): void {
                $summary['units']['scanned']++;
                $expected = $unitResolver->resolve($unit);
                if ($unit->status === $expected) {
                    $summary['units']['canonical']++;
                } else {
                    $summary['units']['changes_required']++;
                    $key = $unit->status->value.' -> '.$expected->value;
                    $summary['unit_status_transitions'][$key] = ($summary['unit_status_transitions'][$key] ?? 0) + 1;
                    if ($apply) {
                        $unit->update(['status' => $expected->value]);
                        $summary['units']['changes_applied']++;
                    }
                }
            });
        });

        return $summary;
    }

    /** @param array<string, mixed> $summary */
    private function detectAnomalies(SalesCase $case, array &$summary): void
    {
        $add = function (string $type, string $detail) use (&$summary, $case): void {
            $summary['anomalies']['total']++;
            $summary['anomalies']['counts'][$type] = ($summary['anomalies']['counts'][$type] ?? 0) + 1;
            $summary['anomalies']['items'][] = ['type' => $type, 'sales_case_id' => $case->id, 'detail' => $detail];
        };

        if ($case->unit_id === null && ($case->developerPpjbs()->exists() || $case->akad()->exists() || $case->bast()->exists())) {
            $add('unit_missing_at_finalization', 'Finalization evidence exists without assigned unit.');
        }
        if ($case->bast()->exists() && $case->case_status->value !== 'COMPLETED') {
            $add('bast_status_mismatch', 'BAST exists but case is not COMPLETED.');
        }
        if ($case->case_status->value === 'COMPLETED' && ! $case->bast()->exists()) {
            $add('completed_without_bast', 'Case is COMPLETED without BAST.');
        }
        if ($case->akad()->exists() && ! $case->developerPpjbs()->exists()) {
            $add('akad_without_ppjb', 'Akad exists without Developer PPJB.');
        }
        if ($case->unit !== null && $case->project_id !== $case->unit->project_id) {
            $add('project_unit_mismatch', 'SalesCase project differs from assigned Unit project.');
        }
        if ($case->project !== null && $case->branch_id !== $case->project->branch_id) {
            $add('branch_project_mismatch', 'SalesCase branch differs from Project branch.');
        }
        if ($case->unit !== null && $case->unit->project?->branch_id !== $case->branch_id) {
            $add('unit_branch_mismatch', 'Assigned Unit belongs to another branch.');
        }
        if ($case->unit_id !== null && SalesCase::query()->where('unit_id', $case->unit_id)->where('case_status', 'ACTIVE')->count() > 1) {
            $add('multiple_active_unit_owners', 'More than one ACTIVE SalesCase points to Unit.');
        }
    }

    /** @param array<string, mixed> $summary */
    private function renderSummary(array $summary): void
    {
        $this->line('MODE: '.$summary['mode']);
        $this->line('BRANCH: '.$summary['branch']['id'].' — '.$summary['branch']['name']);
        $this->line('SALES CASES: '.json_encode($summary['sales_cases']));
        $this->line('UNITS: '.json_encode($summary['units']));
        $this->line('ANOMALIES: '.$summary['anomalies']['total']);
    }

    private function failCommand(bool $json, string $message): int
    {
        if ($json) {
            $this->line(json_encode(['error' => $message], JSON_UNESCAPED_SLASHES));
        } else {
            $this->error($message);
        }

        return self::FAILURE;
    }
}
