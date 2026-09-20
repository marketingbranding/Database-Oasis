<?php

namespace App\Services\Repair;

use App\Repairability;

final readonly class RepairIssue
{
    /** @param array<string, mixed> $evidence */
    public function __construct(
        public string $issueCode,
        public string $salesCaseId,
        public string $branchId,
        public string $targetType,
        public string $targetId,
        public Repairability $repairability,
        public string $diagnosis,
        public array $evidence,
    ) {}

    /** @return array<string, mixed> */
    public function toArray(): array
    {
        return [
            'issue_code' => $this->issueCode,
            'sales_case_id' => $this->salesCaseId,
            'branch_id' => $this->branchId,
            'target_type' => $this->targetType,
            'target_id' => $this->targetId,
            'repairability' => $this->repairability->value,
            'diagnosis' => $this->diagnosis,
            'evidence' => $this->evidence,
        ];
    }
}
