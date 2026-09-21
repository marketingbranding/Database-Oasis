<?php

namespace Tests\Feature;

use App\BankResponseType;
use App\DocumentSubmissionStatus;
use App\DocumentSubmissionType;
use App\Models\Bank;
use App\Models\BankProcess;
use App\Models\Branch;
use App\Models\BusinessNumberSequence;
use App\Models\DocumentSubmission;
use App\Models\Project;
use App\Models\Psjb;
use App\Models\RepairAction;
use App\Models\SalesCase;
use App\Models\Unit;
use App\Models\User;
use App\Repairability;
use App\Services\Repair\BankProcessWithoutSubmissionRule;
use App\Services\Repair\RepairIssue;
use App\Services\Repair\RepairPlan;
use App\Services\TransactionRepairEngine;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Validation\ValidationException;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class R2B1BankProcessLineageTest extends TestCase
{
    use RefreshDatabase;

    public function test_orphan_detection_returns_one_exact_issue_per_bank_process_and_ignores_linked_processes(): void
    {
        $this->seed();
        $branch = Branch::factory()->create(['code' => 'MGL']);
        $case = $this->caseFor($branch);
        $bank = Bank::factory()->create();
        $linkedSubmission = $this->submissionFor($case, $bank, ['submission_date' => '2026-09-01']);
        $linked = $this->processFor($case, $bank, ['document_submission_id' => $linkedSubmission->id]);
        $orphanA = $this->processFor($case, $bank);
        $orphanB = $this->processFor($case, $bank, ['response_date' => '2026-09-21']);
        $engine = app(TransactionRepairEngine::class);

        $issues = array_values(array_filter($engine->scanBranch($branch), fn (RepairIssue $issue): bool => $issue->issueCode === 'bank_process_without_submission'));

        $this->assertCount(2, $issues);
        $this->assertSame([$orphanA->id, $orphanB->id], array_column(array_map(fn (RepairIssue $issue): array => $issue->toArray(), $issues), 'target_id'));
        $this->assertNotContains($linked->id, array_column(array_map(fn (RepairIssue $issue): array => $issue->toArray(), $issues), 'target_id'));
    }

    public function test_single_structural_candidate_is_review_required_and_read_only(): void
    {
        $this->seed();
        $branch = Branch::factory()->create(['code' => 'MGL']);
        $case = $this->caseFor($branch);
        $bank = Bank::factory()->create();
        $submission = $this->submissionFor($case, $bank, ['submission_date' => '2026-09-20']);
        $process = $this->processFor($case, $bank, ['response_date' => '2026-09-20']);
        $engine = app(TransactionRepairEngine::class);

        $issue = $this->orphanIssue($engine, $branch, $process->id);
        $plan = $engine->plan($issue);

        $this->assertSame(BankProcessWithoutSubmissionRule::ExactSingleCandidate, $issue->diagnosis);
        $this->assertSame(Repairability::ReviewRequired, $issue->repairability);
        $this->assertSame(1, $issue->evidence['candidate_count']);
        $this->assertSame($submission->id, $issue->evidence['candidates'][0]['id']);
        $this->assertSame('LINK_EXISTING_DOCUMENT_SUBMISSION_REVIEW', $plan->actionCode);
        $this->assertSame($submission->id, $plan->proposed['document_submission_id']);
        $this->assertSame(0, BusinessNumberSequence::query()->count());
        $this->assertSame(0, RepairAction::query()->count());
        $this->assertNull($process->refresh()->document_submission_id);
        $this->assertSame($submission->id, $submission->refresh()->id);
    }

    public function test_candidate_search_excludes_other_sales_case_other_bank_cash_internal_and_deleted_submissions(): void
    {
        $this->seed();
        $branch = Branch::factory()->create(['code' => 'MGL']);
        $case = $this->caseFor($branch);
        $otherCase = $this->caseFor($branch);
        $bank = Bank::factory()->create();
        $otherBank = Bank::factory()->create();
        $this->submissionFor($otherCase, $bank, ['submission_date' => '2026-09-01']);
        $this->submissionFor($case, $otherBank, ['submission_date' => '2026-09-01']);
        $this->submissionFor($case, $bank, ['type' => DocumentSubmissionType::CashInternal, 'bank_id' => null, 'submission_date' => '2026-09-01']);
        $deleted = $this->submissionFor($case, $bank, ['submission_date' => '2026-09-01']);
        $deleted->delete();
        $process = $this->processFor($case, $bank, ['response_date' => '2026-09-20']);
        $engine = app(TransactionRepairEngine::class);

        $issue = $this->orphanIssue($engine, $branch, $process->id);

        $this->assertSame(BankProcessWithoutSubmissionRule::NoCandidate, $issue->diagnosis);
        $this->assertSame(Repairability::ManualDecisionRequired, $issue->repairability);
        $this->assertSame(0, $issue->evidence['candidate_count']);
        $this->assertSame([], $issue->evidence['candidates']);
    }

    public function test_multiple_candidates_are_manual_decision_required_in_deterministic_order(): void
    {
        $this->seed();
        $branch = Branch::factory()->create(['code' => 'MGL']);
        $case = $this->caseFor($branch);
        $bank = Bank::factory()->create();
        $first = $this->submissionFor($case, $bank, ['submission_date' => '2026-09-01', 'sequence' => 2]);
        $second = $this->submissionFor($case, $bank, ['submission_date' => '2026-09-01', 'sequence' => 1]);
        $process = $this->processFor($case, $bank, ['response_date' => '2026-09-20']);
        $engine = app(TransactionRepairEngine::class);

        $issue = $this->orphanIssue($engine, $branch, $process->id);
        $candidateIds = array_column($issue->evidence['candidates'], 'id');

        $this->assertSame(BankProcessWithoutSubmissionRule::MultipleCandidates, $issue->diagnosis);
        $this->assertSame(Repairability::ManualDecisionRequired, $issue->repairability);
        $this->assertSame(2, $issue->evidence['candidate_count']);
        $this->assertSame([$second->id, $first->id], $candidateIds);
        $this->assertNull($this->enginePlan($engine, $issue)->proposed['document_submission_id']);
    }

    public function test_temporal_conflict_is_manual_decision_required(): void
    {
        $this->seed();
        $branch = Branch::factory()->create(['code' => 'MGL']);
        $case = $this->caseFor($branch);
        $bank = Bank::factory()->create();
        $submission = $this->submissionFor($case, $bank, ['submission_date' => '2026-09-21']);
        $process = $this->processFor($case, $bank, ['response_date' => '2026-09-20']);
        $engine = app(TransactionRepairEngine::class);

        $issue = $this->orphanIssue($engine, $branch, $process->id);

        $this->assertSame(BankProcessWithoutSubmissionRule::TemporalConflict, $issue->diagnosis);
        $this->assertSame(Repairability::ManualDecisionRequired, $issue->repairability);
        $this->assertSame($submission->id, $issue->evidence['candidates'][0]['id']);
        $this->assertSame('CONFLICT', $issue->evidence['candidates'][0]['temporal_relationship']);
    }

    public function test_missing_response_or_submission_dates_are_explicitly_uncertain(): void
    {
        $this->seed();
        $branch = Branch::factory()->create(['code' => 'MGL']);
        $case = $this->caseFor($branch);
        $bank = Bank::factory()->create();
        $missingResponseSubmission = $this->submissionFor($case, $bank, ['submission_date' => '2026-09-20']);
        $missingResponse = $this->processFor($case, $bank, ['response_date' => null, 'is_legacy_import' => true, 'legacy_date_missing' => true]);
        $engine = app(TransactionRepairEngine::class);

        $responseIssue = $this->orphanIssue($engine, $branch, $missingResponse->id);

        $this->assertSame(BankProcessWithoutSubmissionRule::TemporalUncertain, $responseIssue->diagnosis);
        $this->assertSame('UNCERTAIN', $responseIssue->evidence['candidates'][0]['temporal_relationship']);
        $this->assertSame($missingResponseSubmission->id, $responseIssue->evidence['candidates'][0]['id']);

        $missingSubmission = $this->submissionFor($case, $bank, ['submission_date' => null, 'is_legacy_import' => true, 'legacy_date_missing' => true, 'sequence' => 2]);
        $missingSubmissionProcess = $this->processFor($case, $bank, ['response_date' => '2026-09-20']);
        $submissionIssue = $this->orphanIssue($engine, $branch, $missingSubmissionProcess->id);

        $this->assertSame(BankProcessWithoutSubmissionRule::MultipleCandidates, $submissionIssue->diagnosis);
        $this->assertSame($missingSubmission->id, $submissionIssue->evidence['candidates'][1]['id']);
        $this->assertSame('UNCERTAIN', $submissionIssue->evidence['candidates'][1]['temporal_relationship']);
    }

    #[DataProvider('nonAutoClassifications')]
    public function test_non_auto_diagnoses_are_rejected_by_apply_without_linkage_or_side_effects(string $classification): void
    {
        $this->seed();
        $branch = Branch::factory()->create(['code' => 'MGL']);
        $case = $this->caseFor($branch);
        $bank = Bank::factory()->create();
        $process = $this->processFor($case, $bank);
        if ($classification === BankProcessWithoutSubmissionRule::ExactSingleCandidate) {
            $this->submissionFor($case, $bank, ['submission_date' => '2026-09-20']);
        }
        if ($classification === BankProcessWithoutSubmissionRule::MultipleCandidates) {
            $this->submissionFor($case, $bank, ['submission_date' => '2026-09-20']);
            $this->submissionFor($case, $bank, ['submission_date' => '2026-09-20', 'sequence' => 2]);
        }
        if ($classification === BankProcessWithoutSubmissionRule::TemporalConflict) {
            $this->submissionFor($case, $bank, ['submission_date' => '2026-09-21']);
        }
        $engine = app(TransactionRepairEngine::class);
        $issue = $this->orphanIssue($engine, $branch, $process->id);
        $plan = $this->enginePlan($engine, $issue);
        $exception = null;

        try {
            $engine->apply(User::factory()->create(), $plan);
        } catch (ValidationException $exception) {
        }

        $this->assertSame($classification, $issue->diagnosis);
        $this->assertInstanceOf(ValidationException::class, $exception);
        $this->assertNull($process->refresh()->document_submission_id);
        $this->assertSame(0, RepairAction::query()->count());
        $this->assertSame(0, BusinessNumberSequence::query()->count());
    }

    public static function nonAutoClassifications(): array
    {
        return [
            'review required exact candidate' => [BankProcessWithoutSubmissionRule::ExactSingleCandidate],
            'manual multiple candidates' => [BankProcessWithoutSubmissionRule::MultipleCandidates],
            'manual temporal conflict' => [BankProcessWithoutSubmissionRule::TemporalConflict],
            'manual no candidate' => [BankProcessWithoutSubmissionRule::NoCandidate],
        ];
    }

    #[DataProvider('fingerprintMutations')]
    public function test_candidate_fingerprint_changes_when_relevant_fact_changes(string $mutation): void
    {
        $this->seed();
        $branch = Branch::factory()->create(['code' => 'MGL']);
        $case = $this->caseFor($branch);
        $otherCase = $this->caseFor($branch);
        $bank = Bank::factory()->create();
        $otherBank = Bank::factory()->create();
        $submission = $this->submissionFor($case, $bank, ['submission_date' => '2026-09-20']);
        $process = $this->processFor($case, $bank, ['response_date' => '2026-09-20']);
        $engine = app(TransactionRepairEngine::class);
        $original = $this->enginePlan($engine, $this->orphanIssue($engine, $branch, $process->id));

        match ($mutation) {
            'process bank' => $process->update(['bank_id' => $otherBank->id]),
            'response date' => $process->update(['response_date' => '2026-09-21']),
            'candidate bank' => $submission->update(['bank_id' => $otherBank->id]),
            'candidate status' => $submission->update(['status' => DocumentSubmissionStatus::Closed]),
            'candidate date' => $submission->update(['submission_date' => '2026-09-19']),
            'candidate sales case' => $submission->update(['sales_case_id' => $otherCase->id]),
            default => throw new ValidationException(['mutation' => 'Unknown test mutation.']),
        };

        $replanned = $this->enginePlan($engine, $this->orphanIssue($engine, $branch, $process->id));

        $this->assertNotSame($original->fingerprint, $replanned->fingerprint);
        $this->assertSame($process->id, $replanned->issue->targetId);
        $this->assertNull($process->refresh()->document_submission_id);
    }

    public static function fingerprintMutations(): array
    {
        return [
            'process bank' => ['process bank'],
            'response date' => ['response date'],
            'candidate bank' => ['candidate bank'],
            'candidate status' => ['candidate status'],
            'candidate date' => ['candidate date'],
            'candidate sales case' => ['candidate sales case'],
        ];
    }

    public function test_adding_another_candidate_changes_exact_preview_to_manual_multiple(): void
    {
        $this->seed();
        $branch = Branch::factory()->create(['code' => 'MGL']);
        $case = $this->caseFor($branch);
        $bank = Bank::factory()->create();
        $this->submissionFor($case, $bank, ['submission_date' => '2026-09-20']);
        $process = $this->processFor($case, $bank, ['response_date' => '2026-09-20']);
        $engine = app(TransactionRepairEngine::class);
        $original = $this->enginePlan($engine, $this->orphanIssue($engine, $branch, $process->id));
        $this->submissionFor($case, $bank, ['submission_date' => '2026-09-20', 'sequence' => 2]);

        $replanned = $this->enginePlan($engine, $this->orphanIssue($engine, $branch, $process->id));

        $this->assertSame(BankProcessWithoutSubmissionRule::ExactSingleCandidate, $original->issue->diagnosis);
        $this->assertSame(BankProcessWithoutSubmissionRule::MultipleCandidates, $replanned->issue->diagnosis);
        $this->assertNotSame($original->fingerprint, $replanned->fingerprint);
        $this->assertNull($process->refresh()->document_submission_id);
    }

    public function test_scan_is_deterministic_branch_isolated_and_pii_free(): void
    {
        $this->seed();
        $branchA = Branch::factory()->create(['code' => 'MGL']);
        $branchB = Branch::factory()->create(['code' => 'SMG']);
        $caseA = $this->caseFor($branchA);
        $caseB = $this->caseFor($branchB);
        $caseA->consumer->update(['name' => 'SECRET_NAME', 'nik' => 'SECRET_NIK', 'phone' => 'SECRET_PHONE']);
        $processA = $this->processFor($caseA, Bank::factory()->create());
        $processB = $this->processFor($caseB, Bank::factory()->create());
        $engine = app(TransactionRepairEngine::class);

        $first = array_map(fn (RepairIssue $issue): array => $issue->toArray(), $engine->scanBranch($branchA));
        $second = array_map(fn (RepairIssue $issue): array => $issue->toArray(), $engine->scanBranch($branchA));
        $json = json_encode($first, JSON_THROW_ON_ERROR);

        $this->assertSame($first, $second);
        $this->assertCount(1, $first);
        $this->assertSame($processA->id, $first[0]['target_id']);
        $this->assertNotSame($processB->id, $first[0]['target_id']);
        $this->assertStringNotContainsString('SECRET_NAME', $json);
        $this->assertStringNotContainsString('SECRET_NIK', $json);
        $this->assertStringNotContainsString('SECRET_PHONE', $json);
    }

    public function test_preview_and_cli_have_no_side_effects_and_cli_is_deterministic_and_pii_free(): void
    {
        $this->seed();
        $branch = Branch::factory()->create(['code' => 'MGL']);
        $case = $this->caseFor($branch);
        $case->consumer->update(['name' => 'SECRET_NAME', 'nik' => 'SECRET_NIK', 'phone' => 'SECRET_PHONE']);
        $process = $this->processFor($case, Bank::factory()->create());
        $engine = app(TransactionRepairEngine::class);
        $before = $process->getAttributes();
        $plan = $this->enginePlan($engine, $this->orphanIssue($engine, $branch, $process->id));
        $planJson = json_encode($plan->toArray(), JSON_THROW_ON_ERROR);
        $this->assertStringNotContainsString('SECRET_NAME', $planJson);
        $this->assertStringNotContainsString('SECRET_NIK', $planJson);
        $this->assertStringNotContainsString('SECRET_PHONE', $planJson);

        $this->assertSame(0, Artisan::call('oasis:repair-scan', ['--branch-id' => $branch->id, '--json' => true]));
        $first = Artisan::output();
        $this->assertSame(0, Artisan::call('oasis:repair-scan', ['--branch-id' => $branch->id, '--json' => true]));
        $second = Artisan::output();

        $payload = json_decode($first, true, 512, JSON_THROW_ON_ERROR);
        $this->assertSame($first, $second);
        $this->assertSame(['bank_process_without_submission' => 1], $payload['counts']);
        $this->assertSame(['MANUAL_DECISION_REQUIRED' => 1], $payload['repairability_counts']);
        $this->assertStringNotContainsString('SECRET_NAME', $first);
        $this->assertStringNotContainsString('SECRET_NIK', $first);
        $this->assertStringNotContainsString('SECRET_PHONE', $first);
        $this->assertSame($before['document_submission_id'], $process->refresh()->document_submission_id);
        $this->assertSame($before['bank_id'], $process->bank_id);
        $this->assertSame($before['response_type'], $process->response_type->value);
        $this->assertSame(0, DocumentSubmission::query()->count());
        $this->assertSame(0, RepairAction::query()->count());
        $this->assertSame(0, BusinessNumberSequence::query()->count());
        $this->assertSame($case->current_stage, $case->refresh()->current_stage);
        $this->assertSame($case->unit->status, $case->unit->refresh()->status);
    }

    private function caseFor(Branch $branch): SalesCase
    {
        $unit = Unit::factory()->for(Project::factory()->for($branch))->create();

        return SalesCase::factory()->forUnit($unit)->create();
    }

    private function submissionFor(SalesCase $case, Bank $bank, array $attributes = []): DocumentSubmission
    {
        $psjb = Psjb::query()->where('sales_case_id', $case->id)->first() ?? Psjb::factory()->create(['sales_case_id' => $case->id]);

        return DocumentSubmission::query()->create(array_merge([
            'sales_case_id' => $case->id,
            'psjb_id' => $psjb->id,
            'bank_id' => $bank->id,
            'submission_date' => '2026-09-20',
            'sequence' => (DocumentSubmission::query()->where('sales_case_id', $case->id)->max('sequence') ?? 0) + 1,
            'type' => DocumentSubmissionType::Bank,
            'status' => DocumentSubmissionStatus::Submitted,
            'created_by' => User::factory()->create()->id,
        ], $attributes));
    }

    private function processFor(SalesCase $case, Bank $bank, array $attributes = []): BankProcess
    {
        return BankProcess::query()->create(array_merge([
            'sales_case_id' => $case->id,
            'document_submission_id' => null,
            'bank_id' => $bank->id,
            'response_type' => BankResponseType::Approved,
            'response_date' => '2026-09-20',
            'is_authoritative' => false,
            'created_by' => User::factory()->create()->id,
        ], $attributes));
    }

    private function orphanIssue(TransactionRepairEngine $engine, Branch $branch, string $targetId): RepairIssue
    {
        foreach ($engine->scanBranch($branch) as $issue) {
            if ($issue->issueCode === 'bank_process_without_submission' && $issue->targetId === $targetId) {
                return $issue;
            }
        }

        throw new ValidationException(['issue' => 'Expected orphan issue was not detected.']);
    }

    private function enginePlan(TransactionRepairEngine $engine, RepairIssue $issue): RepairPlan
    {
        return $engine->plan($issue);
    }
}
