<?php

namespace App\Services\Repair;

use App\DocumentSubmissionType;
use App\Models\BankProcess;
use App\Models\Branch;
use App\Models\DocumentSubmission;
use App\Models\SalesCase;
use App\Repairability;
use Carbon\CarbonInterface;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Validation\ValidationException;

final class BankProcessWithoutSubmissionRule implements RepairRule
{
    public const ExactSingleCandidate = 'EXACT_SINGLE_CANDIDATE';

    public const MultipleCandidates = 'MULTIPLE_CANDIDATES';

    public const NoCandidate = 'NO_CANDIDATE';

    public const TemporalConflict = 'TEMPORAL_CONFLICT';

    public const TemporalUncertain = 'TEMPORAL_UNCERTAIN';

    public function issueCode(): string
    {
        return 'bank_process_without_submission';
    }

    public function targetType(): string
    {
        return BankProcess::class;
    }

    public function actionCode(): string
    {
        return 'LINK_EXISTING_DOCUMENT_SUBMISSION_REVIEW';
    }

    public function scan(Branch $branch): array
    {
        return BankProcess::query()
            ->whereHas('salesCase', fn ($query) => $query->where('branch_id', $branch->id))
            ->whereNull('document_submission_id')
            ->orderBy('id')
            ->get()
            ->map(fn (BankProcess $process): RepairIssue => $this->detect($process))
            ->all();
    }

    public function findTarget(string $targetId): Model
    {
        return BankProcess::query()->findOrFail($targetId);
    }

    public function lockTarget(string $targetId): Model
    {
        return BankProcess::query()->whereKey($targetId)->lockForUpdate()->firstOrFail();
    }

    public function isIssuePresent(Model $target): bool
    {
        return $target instanceof BankProcess && $target->document_submission_id === null;
    }

    public function detect(Model $target): RepairIssue
    {
        $process = $this->target($target);

        if (! $this->isIssuePresent($process)) {
            throw ValidationException::withMessages(['issue' => 'BankProcess orphan issue no longer exists.']);
        }

        $diagnosis = $this->diagnosis($process);
        $candidates = $this->candidates($process);

        return new RepairIssue(
            $this->issueCode(),
            $process->sales_case_id,
            $process->salesCase->branch_id,
            $this->targetType(),
            $process->id,
            $this->repairability($diagnosis),
            $diagnosis,
            [
                'bank_id' => $process->bank_id,
                'response_type' => $process->response_type->value,
                'response_date' => $this->dateString($process->response_date),
                'is_authoritative' => $process->is_authoritative,
                'sp3k_date' => $this->dateString($process->sp3k_date),
                'candidate_count' => count($candidates),
                'candidate_classification' => $diagnosis,
                'candidates' => array_map(fn (array $candidate): array => $candidate, $candidates),
            ],
        );
    }

    public function before(Model $target): array
    {
        $process = $this->target($target);

        return [
            'sales_case_id' => $process->sales_case_id,
            'bank_process_id' => $process->id,
            'bank_id' => $process->bank_id,
            'document_submission_id' => $process->document_submission_id,
            'response_type' => $process->response_type->value,
            'response_date' => $this->dateString($process->response_date),
            'is_authoritative' => $process->is_authoritative,
            'sp3k_date' => $this->dateString($process->sp3k_date),
        ];
    }

    public function proposed(Model $target): array
    {
        $process = $this->target($target);
        $diagnosis = $this->diagnosis($process);
        $candidates = $this->candidates($process);

        return [
            'document_submission_id' => $diagnosis === self::ExactSingleCandidate ? $candidates[0]['id'] : null,
            'diagnosis' => $diagnosis,
            'review_required' => true,
        ];
    }

    public function fingerprintPayload(Model $target, RepairIssue $issue): array
    {
        $process = $this->target($target);
        $candidates = $this->candidates($process);

        return [
            'issue_code' => $issue->issueCode,
            'target_type' => $issue->targetType,
            'bank_process_id' => $process->id,
            'sales_case_id' => $process->sales_case_id,
            'branch_id' => $process->salesCase->branch_id,
            'bank_id' => $process->bank_id,
            'document_submission_id' => $process->document_submission_id,
            'response_type' => $process->response_type->value,
            'response_date' => $this->dateString($process->response_date),
            'is_authoritative' => $process->is_authoritative,
            'sp3k_date' => $this->dateString($process->sp3k_date),
            'candidate_count' => count($candidates),
            'diagnosis' => $this->diagnosis($process),
            'candidates' => $candidates,
        ];
    }

    public function apply(Model $target): string
    {
        throw ValidationException::withMessages(['repairability' => 'BankProcess lineage requires review before linkage.']);
    }

    public function after(Model $target): array
    {
        return $this->before($target);
    }

    public function verify(Model $target, SalesCase $case, array $before): void
    {
        throw ValidationException::withMessages(['repairability' => 'BankProcess lineage requires review before linkage.']);
    }

    private function diagnosis(BankProcess $process): string
    {
        $candidates = $this->candidates($process);

        if (count($candidates) === 0) {
            return self::NoCandidate;
        }

        if (count($candidates) > 1) {
            return self::MultipleCandidates;
        }

        return match ($candidates[0]['temporal_relationship']) {
            'CONFLICT' => self::TemporalConflict,
            'UNCERTAIN' => self::TemporalUncertain,
            default => self::ExactSingleCandidate,
        };
    }

    private function repairability(string $diagnosis): Repairability
    {
        return $diagnosis === self::ExactSingleCandidate
            ? Repairability::ReviewRequired
            : Repairability::ManualDecisionRequired;
    }

    /** @return list<array<string, mixed>> */
    private function candidates(BankProcess $process): array
    {
        $submissions = DocumentSubmission::query()
            ->where('sales_case_id', $process->sales_case_id)
            ->where('bank_id', $process->bank_id)
            ->where('type', '!=', DocumentSubmissionType::CashInternal->value)
            ->orderByRaw('submission_date IS NULL')
            ->orderBy('submission_date')
            ->orderBy('sequence')
            ->orderBy('id')
            ->get();

        return $submissions->map(function (DocumentSubmission $submission) use ($process): array {
            $submissionDate = $this->dateString($submission->submission_date);
            $responseDate = $this->dateString($process->response_date);

            return [
                'id' => $submission->id,
                'sales_case_id' => $submission->sales_case_id,
                'bank_id' => $submission->bank_id,
                'type' => $submission->type->value,
                'status' => $submission->status->value,
                'submission_date' => $submissionDate,
                'sequence' => $submission->sequence,
                'temporal_relationship' => $submissionDate === null || $responseDate === null
                    ? 'UNCERTAIN'
                    : ($submissionDate <= $responseDate ? 'BEFORE_OR_EQUAL' : 'CONFLICT'),
            ];
        })->all();
    }

    private function dateString(mixed $date): ?string
    {
        return $date instanceof CarbonInterface ? $date->toDateString() : null;
    }

    private function target(Model $target): BankProcess
    {
        if (! $target instanceof BankProcess) {
            throw ValidationException::withMessages(['target' => 'Invalid BankProcess lineage target.']);
        }

        return $target;
    }
}
