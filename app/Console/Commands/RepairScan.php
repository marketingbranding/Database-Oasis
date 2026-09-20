<?php

namespace App\Console\Commands;

use App\Models\Branch;
use App\Services\TransactionRepairEngine;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;

#[Signature('oasis:repair-scan {--branch-id= : Branch to inspect} {--json : Emit deterministic JSON}')]
#[Description('Read-only scan for transaction repair issues.')]
class RepairScan extends Command
{
    public function handle(TransactionRepairEngine $engine): int
    {
        $branchId = $this->option('branch-id');
        if (! is_string($branchId) || $branchId === '') {
            $this->error('branch_id is required');

            return self::FAILURE;
        }
        $branch = Branch::find($branchId);
        if ($branch === null) {
            $this->error('Branch not found.');

            return self::FAILURE;
        }
        $issues = array_map(fn ($issue): array => $issue->toArray(), $engine->scanBranch($branch));
        $counts = [];
        $repairability = [];
        foreach ($issues as $issue) {
            $counts[$issue['issue_code']] = ($counts[$issue['issue_code']] ?? 0) + 1;
            $repairability[$issue['repairability']] = ($repairability[$issue['repairability']] ?? 0) + 1;
        }
        ksort($counts);
        ksort($repairability);
        $payload = ['branch' => ['id' => $branch->id, 'name' => $branch->name], 'counts' => $counts, 'repairability_counts' => $repairability, 'issues' => $issues];
        $this->line(json_encode($payload, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));

        return self::SUCCESS;
    }
}
