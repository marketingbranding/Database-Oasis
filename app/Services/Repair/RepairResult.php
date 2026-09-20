<?php

namespace App\Services\Repair;

final readonly class RepairResult
{
    /** @param array<string, mixed> $after */
    public function __construct(
        public RepairPlan $plan,
        public string $auditId,
        public array $after,
    ) {}

    /** @return array<string, mixed> */
    public function toArray(): array
    {
        return ['audit_id' => $this->auditId, 'issue' => $this->plan->issue->toArray(), 'after' => $this->after];
    }
}
