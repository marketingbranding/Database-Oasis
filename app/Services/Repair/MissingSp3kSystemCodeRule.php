<?php

namespace App\Services\Repair;

use App\Models\BankProcess;
use App\Models\Branch;
use App\Models\SalesCase;
use App\Repairability;
use App\Services\BusinessNumberGenerator;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Validation\ValidationException;

final class MissingSp3kSystemCodeRule implements RepairRule
{
    public function __construct(private BusinessNumberGenerator $numbers) {}

    public function issueCode(): string
    {
        return 'sp3k_system_code_missing';
    }

    public function targetType(): string
    {
        return BankProcess::class;
    }

    public function actionCode(): string
    {
        return 'GENERATE_SP3K_SYSTEM_CODE';
    }

    public function scan(Branch $branch): array
    {
        return BankProcess::query()->whereHas('salesCase', fn ($q) => $q->where('branch_id', $branch->id))->where('is_authoritative', true)->whereNotNull('sp3k_date')->whereNull('sp3k_code')->orderBy('id')->get()->map(fn (BankProcess $process): RepairIssue => $this->detect($process))->all();
    }

    public function lockTarget(string $targetId): Model
    {
        return BankProcess::query()->whereKey($targetId)->lockForUpdate()->firstOrFail();
    }

    public function detect(Model $target): RepairIssue
    {
        if (! $target instanceof BankProcess || ! $target->is_authoritative || $target->sp3k_date === null || $target->sp3k_code !== null) {
            throw ValidationException::withMessages(['issue' => 'SP3K issue no longer exists.']);
        }

        return new RepairIssue($this->issueCode(), $target->sales_case_id, $target->salesCase->branch_id, $this->targetType(), $target->id, Repairability::AutoFixable, 'Canonical SP3K code is missing.', ['is_authoritative' => true, 'sp3k_date' => $target->sp3k_date->toDateString(), 'sp3k_code' => null]);
    }

    public function before(Model $target): array
    {
        $process = $this->target($target);

        return ['sp3k_code' => $process->sp3k_code, 'sp3k_number' => $process->sp3k_number, 'sp3k_date' => $process->sp3k_date?->toDateString(), 'is_authoritative' => $process->is_authoritative];
    }

    public function proposed(Model $target): array
    {
        return ['sp3k_code' => 'SYSTEM GENERATED', 'format' => 'SP3K-{BRANCH}-{YEAR}-{SEQUENCE}'];
    }

    public function fingerprintPayload(Model $target, RepairIssue $issue): array
    {
        $process = $this->target($target);

        return ['issue_code' => $issue->issueCode, 'sales_case_id' => $issue->salesCaseId, 'branch_id' => $issue->branchId, 'target_type' => $issue->targetType, 'target_id' => $issue->targetId, 'is_authoritative' => $process->is_authoritative, 'sp3k_date' => $process->sp3k_date?->toDateString(), 'sp3k_code' => $process->sp3k_code];
    }

    public function apply(Model $target): string
    {
        return $this->numbers->ensureSp3kCode($this->target($target));
    }

    /** @param array<string, mixed> $before */
    public function verify(Model $target, SalesCase $case, array $before): void
    {
        $process = $this->target($target);
        $process->refresh();
        if ($process->sales_case_id !== $case->id || ! $process->is_authoritative || $process->sp3k_date === null || $process->sp3k_code === null || $process->sp3k_number !== $before['sp3k_number'] || $process->sp3k_date->toDateString() !== $before['sp3k_date']) {
            throw ValidationException::withMessages(['repair' => 'SP3K repair verification failed.']);
        }
        try {
            $this->detect($target);
            throw ValidationException::withMessages(['repair' => 'SP3K issue still exists.']);
        } catch (ValidationException $exception) {
            if (($exception->errors()['issue'] ?? []) === []) {
                throw $exception;
            }
        }
    }

    private function target(Model $target): BankProcess
    {
        if (! $target instanceof BankProcess) {
            throw ValidationException::withMessages(['target' => 'Invalid SP3K repair target.']);
        }

        return $target;
    }
}
