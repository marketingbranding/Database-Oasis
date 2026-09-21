<?php

namespace App\Services\Repair;

use App\Models\DocumentSubmission;
use Illuminate\Database\Eloquent\Model;

interface ReviewedRepairRule extends RepairRule
{
    public function canReview(RepairIssue $issue): bool;

    public function reviewedSnapshot(Model $target): BankProcessLineageReviewSnapshot;

    public function applyReviewed(Model $target, DocumentSubmission $candidate): void;

    /** @param array<string, mixed> $before */
    public function verifyReviewed(Model $target, DocumentSubmission $candidate, array $before): void;
}
