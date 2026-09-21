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
use App\UserRole;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Database\Events\QueryExecuted;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class R2B2ReviewedBankProcessLinkageTest extends TestCase
{
    use RefreshDatabase;

    public function test_reviewed_exact_candidate_links_only_orphan_and_audits_without_business_state_changes(): void
    {
        $this->seed();
        [$branch, $case, $process, $submission] = $this->exactFixture();
        $engine = app(TransactionRepairEngine::class);
        $plan = $this->planFor($engine, $branch, $process);
        $user = $this->userWithRole(UserRole::HqAdmin);
        $stage = $case->current_stage;
        $unitStatus = $case->unit->status;
        $submissionBefore = $submission->getAttributes();
        ksort($submissionBefore);

        $result = $engine->applyReviewed($user, $plan, $submission->id);
        $audit = RepairAction::query()->firstOrFail();

        $this->assertTrue($result->verified);
        $this->assertSame($submission->id, $process->refresh()->document_submission_id);
        $submissionAfter = $submission->refresh();
        $this->assertSame($submissionBefore['sales_case_id'], $submissionAfter->sales_case_id);
        $this->assertSame($submissionBefore['bank_id'], $submissionAfter->bank_id);
        $this->assertSame($submissionBefore['submission_date'], $submissionAfter->getRawOriginal('submission_date'));
        $this->assertSame($submissionBefore['sequence'], $submissionAfter->sequence);
        $this->assertSame($submissionBefore['status'], $submissionAfter->getRawOriginal('status'));
        $this->assertSame($submissionBefore['type'], $submissionAfter->getRawOriginal('type'));
        $this->assertSame(1, RepairAction::query()->count());
        $this->assertSame($case->branch_id, $audit->branch_id);
        $this->assertSame($case->id, $audit->sales_case_id);
        $this->assertSame('bank_process_without_submission', $audit->issue_code);
        $this->assertSame(BankProcess::class, $audit->target_type);
        $this->assertSame($process->id, $audit->target_id);
        $this->assertSame('LINK_EXISTING_DOCUMENT_SUBMISSION_REVIEW', $audit->action_code);
        $this->assertSame(Repairability::ReviewRequired, $audit->repairability);
        $this->assertSame($plan->fingerprint, $audit->plan_fingerprint);
        $this->assertNull($audit->before_payload['document_submission_id']);
        $this->assertSame($submission->id, $audit->after_payload['document_submission_id']);
        $this->assertSame($submission->id, $audit->evidence_payload['candidates'][0]['id']);
        $this->assertSame($stage, $case->refresh()->current_stage);
        $this->assertSame($unitStatus, $case->unit->refresh()->status);
        $this->assertSame(0, BusinessNumberSequence::query()->count());
        $this->assertNotContains($process->id, array_column(array_map(fn (RepairIssue $issue): array => $issue->toArray(), $engine->scanBranch($branch)), 'target_id'));
    }

    #[DataProvider('invalidSelections')]
    public function test_reviewed_apply_rejects_invalid_selected_candidate_without_mutation(string $selection): void
    {
        $this->seed();
        [$branch, $case, $process, $submission] = $this->exactFixture();
        $otherCase = $this->caseFor($branch);
        $otherBank = Bank::factory()->create();
        $foreign = $this->submissionFor($otherCase, $otherBank, ['submission_date' => '2026-09-20']);
        $otherBankSubmission = $this->submissionFor($case, $otherBank, ['submission_date' => '2026-09-20']);
        $cash = $this->submissionFor($case, null, ['type' => DocumentSubmissionType::CashInternal, 'bank_id' => null, 'submission_date' => '2026-09-20']);
        $deleted = $this->submissionFor($case, $submission->bank_id, ['submission_date' => '2026-09-20']);
        $deleted->delete();
        $selected = match ($selection) {
            'different candidate' => $foreign->id,
            'foreign' => $foreign->id,
            'different bank' => $otherBankSubmission->id,
            'cash' => $cash->id,
            'deleted' => $deleted->id,
            'nonexistent' => '01invalid000000000000000000',
        };
        $plan = $this->planFor(app(TransactionRepairEngine::class), $branch, $process);
        $exception = null;

        try {
            app(TransactionRepairEngine::class)->applyReviewed($this->userWithRole(UserRole::HqAdmin), $plan, $selected);
        } catch (ValidationException $exception) {
        }

        $this->assertInstanceOf(ValidationException::class, $exception);
        $this->assertNull($process->refresh()->document_submission_id);
        $this->assertSame(0, RepairAction::query()->count());
        $this->assertSame(0, BusinessNumberSequence::query()->count());
    }

    public static function invalidSelections(): array
    {
        return [
            'different candidate' => ['different candidate'],
            'foreign sales case and bank' => ['foreign'],
            'different bank' => ['different bank'],
            'cash internal' => ['cash'],
            'deleted' => ['deleted'],
            'nonexistent' => ['nonexistent'],
        ];
    }

    #[DataProvider('manualClassifications')]
    public function test_reviewed_apply_rejects_manual_classifications_even_with_candidate_id(string $classification): void
    {
        $this->seed();
        [$branch, $case, $process, $submission] = $this->fixtureForClassification($classification);
        $engine = app(TransactionRepairEngine::class);
        $plan = $this->planFor($engine, $branch, $process);
        $exception = null;

        try {
            $engine->applyReviewed($this->userWithRole(UserRole::HqAdmin), $plan, $submission?->id ?? '01candidate0000000000000000');
        } catch (ValidationException $exception) {
        }

        $this->assertSame($classification, $plan->issue->diagnosis);
        $this->assertInstanceOf(ValidationException::class, $exception);
        $this->assertNull($process->refresh()->document_submission_id);
        $this->assertSame(0, RepairAction::query()->count());
    }

    public static function manualClassifications(): array
    {
        return [
            'multiple' => [BankProcessWithoutSubmissionRule::MultipleCandidates],
            'none' => [BankProcessWithoutSubmissionRule::NoCandidate],
            'conflict' => [BankProcessWithoutSubmissionRule::TemporalConflict],
            'uncertain' => [BankProcessWithoutSubmissionRule::TemporalUncertain],
        ];
    }

    #[DataProvider('staleMutations')]
    public function test_reviewed_apply_rejects_stale_exact_plan_before_mutation(string $mutation): void
    {
        $this->seed();
        [$branch, $case, $process, $submission] = $this->exactFixture();
        $engine = app(TransactionRepairEngine::class);
        $plan = $this->planFor($engine, $branch, $process);
        $otherBank = Bank::factory()->create();

        match ($mutation) {
            'add candidate' => $this->submissionFor($case, $submission->bank_id, ['submission_date' => '2026-09-20', 'sequence' => 2]),
            'candidate bank' => $submission->update(['bank_id' => $otherBank->id]),
            'candidate case' => $submission->update(['sales_case_id' => $this->caseFor($branch)->id]),
            'candidate conflict' => $submission->update(['submission_date' => '2026-09-21']),
            'candidate deleted' => $submission->delete(),
            'process date' => $process->update(['response_date' => '2026-09-21']),
            'process bank' => $process->update(['bank_id' => $otherBank->id]),
        };
        $exception = null;

        try {
            $engine->applyReviewed($this->userWithRole(UserRole::HqAdmin), $plan, $submission->id);
        } catch (ValidationException $exception) {
        }

        $this->assertInstanceOf(ValidationException::class, $exception);
        $this->assertNull($process->refresh()->document_submission_id);
        $this->assertSame(0, RepairAction::query()->count());
        $this->assertSame(0, BusinessNumberSequence::query()->count());
    }

    public static function staleMutations(): array
    {
        return [
            'candidate added' => ['add candidate'],
            'candidate bank changed' => ['candidate bank'],
            'candidate case changed' => ['candidate case'],
            'candidate date conflict' => ['candidate conflict'],
            'candidate deleted' => ['candidate deleted'],
            'process response date changed' => ['process date'],
            'process bank changed' => ['process bank'],
        ];
    }

    public function test_reviewed_apply_rejects_wrong_action_unknown_rule_and_auto_fixable_plan(): void
    {
        $this->seed();
        [$branch, $case, $process, $submission] = $this->exactFixture();
        $engine = app(TransactionRepairEngine::class);
        $plan = $this->planFor($engine, $branch, $process);
        $wrongAction = new RepairPlan($plan->issue, 'WRONG_ACTION', $plan->before, $plan->proposed, $plan->fingerprint);
        $exception = null;

        try {
            $engine->applyReviewed($this->userWithRole(UserRole::HqAdmin), $wrongAction, $submission->id);
        } catch (ValidationException $exception) {
        }

        $this->assertInstanceOf(ValidationException::class, $exception);
        $this->assertNull($process->refresh()->document_submission_id);

        $forgedIssue = new RepairIssue('unknown_issue', $case->id, $branch->id, Project::class, $process->id, Repairability::ReviewRequired, BankProcessWithoutSubmissionRule::ExactSingleCandidate, []);
        $forgedPlan = new RepairPlan($forgedIssue, 'LINK_EXISTING_DOCUMENT_SUBMISSION_REVIEW', [], ['document_submission_id' => $submission->id], 'forged');
        $exception = null;

        try {
            $engine->applyReviewed($this->userWithRole(UserRole::HqAdmin), $forgedPlan, $submission->id);
        } catch (ValidationException $exception) {
        }

        $this->assertInstanceOf(ValidationException::class, $exception);
        $this->assertNull($process->refresh()->document_submission_id);
        $linkedSubmission = $this->submissionFor($case, $submission->bank_id, ['sequence' => 2]);
        $sp3k = BankProcess::query()->create(['sales_case_id' => $case->id, 'document_submission_id' => $linkedSubmission->id, 'bank_id' => $submission->bank_id, 'response_type' => BankResponseType::Approved, 'response_date' => '2026-09-20', 'sp3k_date' => '2026-09-20', 'is_authoritative' => true, 'created_by' => User::factory()->create()->id]);
        $sp3kIssue = $this->planForIssue($engine, $branch, $sp3k->id, 'sp3k_system_code_missing');
        $exception = null;

        try {
            $engine->applyReviewed($this->userWithRole(UserRole::HqAdmin), $sp3kIssue, $submission->id);
        } catch (ValidationException $exception) {
        }

        $this->assertInstanceOf(ValidationException::class, $exception);
        $this->assertSame($linkedSubmission->id, $sp3k->refresh()->document_submission_id);
        $this->assertSame(0, RepairAction::query()->count());
    }

    public function test_reviewed_apply_authorizes_case_and_enforces_replay_rejection(): void
    {
        $this->seed();
        [$branch, $case, $process, $submission] = $this->exactFixture();
        $engine = app(TransactionRepairEngine::class);
        $plan = $this->planFor($engine, $branch, $process);
        $otherBranch = Branch::factory()->create(['code' => 'SMG']);
        $denied = $this->userWithRole(UserRole::BranchAdmin, $otherBranch);
        $exception = null;

        try {
            $engine->applyReviewed($denied, $plan, $submission->id);
        } catch (AuthorizationException $exception) {
        }

        $this->assertInstanceOf(AuthorizationException::class, $exception);
        $this->assertNull($process->refresh()->document_submission_id);
        $engine->applyReviewed($this->userWithRole(UserRole::HqAdmin), $plan, $submission->id);
        $exception = null;

        try {
            $engine->applyReviewed($this->userWithRole(UserRole::HqAdmin), $plan, $submission->id);
        } catch (ValidationException $exception) {
        }

        $this->assertInstanceOf(ValidationException::class, $exception);
        $this->assertSame(1, RepairAction::query()->count());
        $this->assertSame($submission->id, $process->refresh()->document_submission_id);
    }

    public function test_reviewed_apply_rolls_back_linkage_when_audit_insert_fails(): void
    {
        $this->seed();
        [$branch, $case, $process, $submission] = $this->exactFixture();
        $engine = app(TransactionRepairEngine::class);
        $plan = $this->planFor($engine, $branch, $process);
        $failureArmed = true;
        DB::listen(function (QueryExecuted $query) use (&$failureArmed): void {
            if ($failureArmed && str_contains(strtolower($query->sql), 'insert into "repair_actions"')) {
                $failureArmed = false;
                throw ValidationException::withMessages(['audit' => 'Test-only audit failure.']);
            }
        });
        $stage = $case->current_stage;
        $unitStatus = $case->unit->status;
        $exception = null;

        try {
            $engine->applyReviewed($this->userWithRole(UserRole::HqAdmin), $plan, $submission->id);
        } catch (ValidationException $exception) {
        }

        $this->assertInstanceOf(ValidationException::class, $exception);
        $this->assertNull($process->refresh()->document_submission_id);
        $this->assertSame(0, RepairAction::query()->count());
        $this->assertSame(0, BusinessNumberSequence::query()->count());
        $this->assertSame($stage, $case->refresh()->current_stage);
        $this->assertSame($unitStatus, $case->unit->refresh()->status);
        $this->assertSame($submission->id, $submission->refresh()->id);
    }

    public function test_reviewed_plan_and_audit_are_pii_free(): void
    {
        $this->seed();
        [$branch, $case, $process, $submission] = $this->exactFixture();
        $case->consumer->update(['name' => 'SECRET_NAME', 'nik' => 'SECRET_NIK', 'phone' => 'SECRET_PHONE']);
        $engine = app(TransactionRepairEngine::class);
        $plan = $this->planFor($engine, $branch, $process);
        $result = $engine->applyReviewed($this->userWithRole(UserRole::HqAdmin), $plan, $submission->id);
        $serialized = json_encode([$plan->toArray(), $result->toArray(), RepairAction::query()->firstOrFail()->toArray()], JSON_THROW_ON_ERROR);

        $this->assertStringNotContainsString('SECRET_NAME', $serialized);
        $this->assertStringNotContainsString('SECRET_NIK', $serialized);
        $this->assertStringNotContainsString('SECRET_PHONE', $serialized);
    }

    private function exactFixture(): array
    {
        $branch = Branch::factory()->create(['code' => 'MGL']);
        $case = $this->caseFor($branch);
        $bank = Bank::factory()->create();
        $submission = $this->submissionFor($case, $bank, ['submission_date' => '2026-09-20']);
        $process = $this->processFor($case, $bank, ['response_date' => '2026-09-20']);

        return [$branch, $case, $process, $submission];
    }

    private function fixtureForClassification(string $classification): array
    {
        [$branch, $case, $process, $submission] = $this->exactFixture();
        if ($classification === BankProcessWithoutSubmissionRule::MultipleCandidates) {
            $this->submissionFor($case, $submission->bank_id, ['submission_date' => '2026-09-20', 'sequence' => 2]);
        }
        if ($classification === BankProcessWithoutSubmissionRule::NoCandidate) {
            $submission->delete();
            $submission = null;
        }
        if ($classification === BankProcessWithoutSubmissionRule::TemporalConflict) {
            $submission->update(['submission_date' => '2026-09-21']);
        }
        if ($classification === BankProcessWithoutSubmissionRule::TemporalUncertain) {
            $process->update(['response_date' => null, 'is_legacy_import' => true, 'legacy_date_missing' => true]);
        }

        return [$branch, $case, $process, $submission];
    }

    private function planForIssue(TransactionRepairEngine $engine, Branch $branch, string $targetId, string $issueCode): RepairPlan
    {
        foreach ($engine->scanBranch($branch) as $issue) {
            if ($issue->issueCode === $issueCode && $issue->targetId === $targetId) {
                return $engine->plan($issue);
            }
        }

        throw new ValidationException(['issue' => 'Expected repair issue not found.']);
    }

    private function planFor(TransactionRepairEngine $engine, Branch $branch, BankProcess $process): RepairPlan
    {
        foreach ($engine->scanBranch($branch) as $issue) {
            if ($issue->issueCode === 'bank_process_without_submission' && $issue->targetId === $process->id) {
                return $engine->plan($issue);
            }
        }

        throw new ValidationException(['issue' => 'Expected orphan issue not found.']);
    }

    private function caseFor(Branch $branch): SalesCase
    {
        return SalesCase::factory()->forUnit(Unit::factory()->for(Project::factory()->for($branch))->create())->create();
    }

    private function submissionFor(SalesCase $case, Bank|string|null $bank, array $attributes = []): DocumentSubmission
    {
        $psjb = $case->psjbs()->first() ?? Psjb::factory()->create(['sales_case_id' => $case->id]);
        $bankId = $bank instanceof Bank ? $bank->id : $bank;

        return DocumentSubmission::query()->create(array_merge([
            'sales_case_id' => $case->id,
            'psjb_id' => $psjb->id,
            'bank_id' => $bankId,
            'submission_date' => '2026-09-20',
            'sequence' => (DocumentSubmission::query()->where('sales_case_id', $case->id)->max('sequence') ?? 0) + 1,
            'status' => DocumentSubmissionStatus::Submitted,
            'type' => DocumentSubmissionType::Bank,
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

    private function userWithRole(UserRole $role, ?Branch $branch = null): User
    {
        $user = User::factory()->create(['branch_id' => $branch?->id]);
        $user->assignRole($role);

        return $user;
    }
}
