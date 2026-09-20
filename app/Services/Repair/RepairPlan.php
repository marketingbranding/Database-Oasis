<?php

namespace App\Services\Repair;

final readonly class RepairPlan
{
    /**
     * @param  array<string, mixed>  $before
     * @param  array<string, mixed>  $proposed
     */
    public function __construct(
        public RepairIssue $issue,
        public string $actionCode,
        public array $before,
        public array $proposed,
        public string $fingerprint,
    ) {}

    /** @return array<string, mixed> */
    public function toArray(): array
    {
        return [
            'issue' => $this->issue->toArray(),
            'action_code' => $this->actionCode,
            'repairability' => $this->issue->repairability->value,
            'before' => $this->before,
            'proposed' => $this->proposed,
            'fingerprint' => $this->fingerprint,
        ];
    }
}
