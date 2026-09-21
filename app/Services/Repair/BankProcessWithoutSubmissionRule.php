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
use Illuminate\Support\Collection;
use Illuminate\Validation\ValidationException;

final class BankProcessWithoutSubmissionRule implements ReviewedRepairRule
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

        return $this->issueFromCandidates($process, $this->candidates($process));
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

    public function canReview(RepairIssue $issue): bool
    {
        return $issue->issueCode === $this->issueCode()
            && $issue->targetType === $this->targetType()
            && $issue->diagnosis === self::ExactSingleCandidate
            && $issue->repairability === Repairability::ReviewRequired;
    }

    public function reviewedSnapshot(Model $target): BankProcessLineageReviewSnapshot
    {
        $process = $this->target($target);
        $submissions = $this->lockedSubmissions($process);
        $candidates = $this->candidates($process, $submissions);
        $issue = $this->issueFromCandidates($process, $candidates);
        $before = $this->before($process);
        $proposed = [
            'document_submission_id' => $issue->diagnosis === self::ExactSingleCandidate ? $candidates[0]['id'] : null,
            'diagnosis' => $issue->diagnosis,
            'review_required' => true,
        ];

        return new BankProcessLineageReviewSnapshot(
            $process,
            $this->candidateModel($submissions, $candidates),
            $issue,
            $before,
            $proposed,
            $this->fingerprintPayloadHash($process, $issue, $candidates),
        );
    }

    public function applyReviewed(Model $target, DocumentSubmission $candidate): void
    {
        $process = $this->target($target);
        $process->document_submission_id = $candidate->id;
        $process->save();
    }

    /** @param array<string, mixed> $before */
    public function verifyReviewed(Model $target, DocumentSubmission $candidate, array $before): void
    {
        $process = $this->target($target)->refresh();
        $candidate->refresh();

        if ($process->document_submission_id !== $candidate->id
            || $candidate->sales_case_id !== $process->sales_case_id
            || $candidate->bank_id !== $process->bank_id
            || $candidate->type !== DocumentSubmissionType::Bank
            || $candidate->trashed()) {
            throw ValidationException::withMessages(['target' => 'Reviewed BankProcess linkage failed verification.']);
        }
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

    /**
     * @param  Collection<int, DocumentSubmission>|null  $submissions
     * @return list<array<string, mixed>>
     */
    private function candidates(BankProcess $process, ?Collection $submissions = null): array
    {
        $submissions ??= DocumentSubmission::query()
            ->where('sales_case_id', $process->sales_case_id)
            ->where('bank_id', $process->bank_id)
            ->where('type', '!=', DocumentSubmissionType::CashInternal->value)
            ->orderByRaw('submission_date IS NULL')
            ->orderBy('submission_date')
            ->orderBy('sequence')
            ->orderBy('id')
            ->get();

        $candidates = $submissions
            ->filter(fn (DocumentSubmission $submission): bool => $submission->sales_case_id === $process->sales_case_id
                && $submission->bank_id === $process->bank_id
                && $submission->type !== DocumentSubmissionType::CashInternal)
            ->map(function (DocumentSubmission $submission) use ($process): array {
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
            })
            ->all();

        usort($candidates, function (array $left, array $right): int {
            if ($left['submission_date'] === null && $right['submission_date'] !== null) {
                return 1;
            }
            if ($left['submission_date'] !== null && $right['submission_date'] === null) {
                return -1;
            }

            return [$left['submission_date'], $left['sequence'], $left['id']] <=> [$right['submission_date'], $right['sequence'], $right['id']];
        });

        return $candidates;
    }

    /** @param list<array<string, mixed>> $candidates */
    private function issueFromCandidates(BankProcess $process, array $candidates): RepairIssue
    {
        $diagnosis = $this->diagnosisFromCandidates($candidates);

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
                'candidates' => $candidates,
            ],
        );
    }

    /** @param list<array<string, mixed>> $candidates */
    private function diagnosisFromCandidates(array $candidates): string
    {
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

    /** @return Collection<int, DocumentSubmission> */
    private function lockedSubmissions(BankProcess $process): Collection
    {
        return DocumentSubmission::query()
            ->where('sales_case_id', $process->sales_case_id)
            ->lockForUpdate()
            ->orderBy('id')
            ->get();
    }

    /**
     * @param  Collection<int, DocumentSubmission>  $submissions
     * @param  list<array<string, mixed>>  $candidates
     */
    private function candidateModel(Collection $submissions, array $candidates): ?DocumentSubmission
    {
        if (count($candidates) !== 1 || $this->diagnosisFromCandidates($candidates) !== self::ExactSingleCandidate) {
            return null;
        }

        return $submissions->firstWhere('id', $candidates[0]['id']);
    }

    /** @param list<array<string, mixed>> $candidates */
    private function fingerprintPayloadHash(BankProcess $process, RepairIssue $issue, array $candidates): string
    {
        $payload = [
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
            'diagnosis' => $issue->diagnosis,
            'candidates' => $candidates,
        ];
        ksort($payload);

        return hash('sha256', json_encode($payload, JSON_THROW_ON_ERROR));
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
