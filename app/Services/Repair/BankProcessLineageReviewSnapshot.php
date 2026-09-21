<?php

namespace App\Services\Repair;

use App\Models\BankProcess;
use App\Models\DocumentSubmission;

final readonly class BankProcessLineageReviewSnapshot
{
    /**
     * @param  array<string, mixed>  $before
     * @param  array<string, mixed>  $proposed
     */
    public function __construct(
        public BankProcess $target,
        public ?DocumentSubmission $candidate,
        public RepairIssue $issue,
        public array $before,
        public array $proposed,
        public string $fingerprint,
    ) {}
}
