<?php

namespace App\Services\Repair;

use App\Models\Branch;
use App\Models\DeveloperPpjb;
use App\Models\SalesCase;
use App\Repairability;
use App\Services\BusinessNumberGenerator;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Validation\ValidationException;

final class MissingPpjbSystemCodeRule implements RepairRule
{
    public function __construct(private BusinessNumberGenerator $numbers) {}

    public function issueCode(): string
    {
        return 'ppjb_system_code_missing';
    }

    public function targetType(): string
    {
        return DeveloperPpjb::class;
    }

    public function actionCode(): string
    {
        return 'GENERATE_PPJB_SYSTEM_CODE';
    }

    public function scan(Branch $branch): array
    {
        return DeveloperPpjb::query()->whereHas('salesCase', fn ($q) => $q->where('branch_id', $branch->id))->whereNull('ppjb_code')->orderBy('id')->get()->map(fn (DeveloperPpjb $ppjb): RepairIssue => $this->detect($ppjb))->all();
    }

    public function findTarget(string $targetId): Model
    {
        return DeveloperPpjb::query()->findOrFail($targetId);
    }

    public function lockTarget(string $targetId): Model
    {
        return DeveloperPpjb::query()->whereKey($targetId)->lockForUpdate()->firstOrFail();
    }

    public function isIssuePresent(Model $target): bool
    {
        return $target instanceof DeveloperPpjb && $target->ppjb_code === null;
    }

    public function detect(Model $target): RepairIssue
    {
        if (! $this->isIssuePresent($target)) {
            throw ValidationException::withMessages(['issue' => 'PPJB issue no longer exists.']);
        }

        $ppjb = $this->target($target);

        return new RepairIssue($this->issueCode(), $ppjb->sales_case_id, $ppjb->salesCase->branch_id, $this->targetType(), $ppjb->id, Repairability::AutoFixable, 'Canonical PPJB code is missing.', ['document_date' => $ppjb->document_date->toDateString(), 'status' => $ppjb->status->value, 'ppjb_code' => null]);
    }

    public function before(Model $target): array
    {
        $ppjb = $this->target($target);

        return ['ppjb_code' => $ppjb->ppjb_code, 'document_number' => $ppjb->document_number, 'document_date' => $ppjb->document_date->toDateString(), 'status' => $ppjb->status->value, 'bank_process_id' => $ppjb->bank_process_id];
    }

    public function proposed(Model $target): array
    {
        return ['ppjb_code' => 'SYSTEM GENERATED', 'format' => 'PPJB-{BRANCH}-{YEAR}-{SEQUENCE}'];
    }

    public function fingerprintPayload(Model $target, RepairIssue $issue): array
    {
        $ppjb = $this->target($target);

        return ['issue_code' => $issue->issueCode, 'sales_case_id' => $issue->salesCaseId, 'branch_id' => $issue->branchId, 'target_type' => $issue->targetType, 'target_id' => $issue->targetId, 'document_date' => $ppjb->document_date->toDateString(), 'status' => $ppjb->status->value, 'ppjb_code' => $ppjb->ppjb_code];
    }

    public function apply(Model $target): string
    {
        return $this->numbers->ensurePpjbCode($this->target($target));
    }

    public function after(Model $target): array
    {
        return $this->before($this->target($target)->refresh());
    }

    /** @param array<string, mixed> $before */
    public function verify(Model $target, SalesCase $case, array $before): void
    {
        $ppjb = $this->target($target);
        $ppjb->refresh();
        if ($ppjb->sales_case_id !== $case->id || $ppjb->ppjb_code === null || $ppjb->document_number !== $before['document_number'] || $ppjb->document_date->toDateString() !== $before['document_date'] || $ppjb->status->value !== $before['status'] || $ppjb->bank_process_id !== $before['bank_process_id'] || $this->isIssuePresent($ppjb)) {
            throw ValidationException::withMessages(['repair' => 'PPJB repair verification failed.']);
        }
    }

    private function target(Model $target): DeveloperPpjb
    {
        if (! $target instanceof DeveloperPpjb) {
            throw ValidationException::withMessages(['target' => 'Invalid PPJB repair target.']);
        }

        return $target;
    }
}
