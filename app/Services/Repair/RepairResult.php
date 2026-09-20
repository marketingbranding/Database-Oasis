<?php

namespace App\Services\Repair;

final readonly class RepairResult
{
    /**
     * @param  array<string, mixed>  $before
     * @param  array<string, mixed>  $after
     */
    public function __construct(
        public RepairPlan $plan,
        public string $auditId,
        public array $before,
        public array $after,
        public string $stageBefore,
        public string $stageAfter,
        public ?string $unitStatusBefore,
        public ?string $unitStatusAfter,
        public bool $verified,
    ) {}

    /** @return array<string, mixed> */
    public function toArray(): array
    {
        return ['audit_id' => $this->auditId, 'issue' => $this->plan->issue->toArray(), 'before' => $this->before, 'after' => $this->after, 'stage_before' => $this->stageBefore, 'stage_after' => $this->stageAfter, 'unit_status_before' => $this->unitStatusBefore, 'unit_status_after' => $this->unitStatusAfter, 'verified' => $this->verified];
    }
}
