<?php

namespace Tests\Feature;

use App\BankResponseType;
use App\DeveloperPpjbStatus;
use App\Enums\BusinessNumberType;
use App\Models\BankProcess;
use App\Models\Branch;
use App\Models\BusinessNumberSequence;
use App\Models\DeveloperPpjb;
use App\Models\Project;
use App\Models\RepairAction;
use App\Models\SalesCase;
use App\Models\Unit;
use App\Models\User;
use App\Repairability;
use App\SalesCaseStage;
use App\Services\BusinessNumberGenerator;
use App\Services\Repair\RepairIssue;
use App\Services\Repair\RepairPlan;
use App\Services\TransactionRepairEngine;
use App\UnitStatus;
use App\UserRole;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Database\Events\QueryExecuted;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class TransactionRepairEngineTest extends TestCase
{
    use RefreshDatabase;

    public function test_detection_excludes_completed_and_non_auto_fixable_sp3k_states_and_detects_each_ppjb_target(): void
    {
        $this->seed();
        $branch = Branch::factory()->create(['code' => 'MGL']);
        $case = $this->caseFor($branch);
        $completedSp3kCase = $this->caseFor($branch);
        $nonAuthoritativeCase = $this->caseFor($branch);
        $missingDateCase = $this->caseFor($branch);
        $completedPpjbCase = $this->caseFor($branch);
        $engine = app(TransactionRepairEngine::class);
        $missingSp3k = $this->sp3kFor($case, ['sp3k_date' => '2026-09-20']);
        $completedSp3k = $this->sp3kFor($completedSp3kCase, ['sp3k_code' => 'COMPLETED-SP3K', 'sp3k_date' => '2026-09-20', 'is_authoritative' => false]);
        $nonAuthoritative = $this->sp3kFor($nonAuthoritativeCase, ['is_authoritative' => false, 'sp3k_date' => '2026-09-20']);
        $missingDate = $this->sp3kFor($missingDateCase, ['sp3k_date' => null]);
        $active = DeveloperPpjb::factory()->create(['sales_case_id' => $case->id, 'status' => DeveloperPpjbStatus::Active]);
        $superseded = DeveloperPpjb::factory()->create(['sales_case_id' => $case->id, 'status' => DeveloperPpjbStatus::Superseded]);
        $cancelled = DeveloperPpjb::factory()->create(['sales_case_id' => $case->id, 'status' => DeveloperPpjbStatus::Cancelled]);
        $completedPpjb = DeveloperPpjb::factory()->create(['sales_case_id' => $completedPpjbCase->id, 'ppjb_code' => 'COMPLETED-PPJB']);

        $issues = $engine->scanBranch($branch);
        $targets = array_map(fn (RepairIssue $issue): string => $issue->targetId, $issues);

        $this->assertCount(4, $issues);
        $this->assertContains($missingSp3k->id, $targets);
        $this->assertContains($active->id, $targets);
        $this->assertContains($superseded->id, $targets);
        $this->assertContains($cancelled->id, $targets);
        $this->assertNotContains($completedSp3k->id, $targets);
        $this->assertNotContains($nonAuthoritative->id, $targets);
        $this->assertNotContains($missingDate->id, $targets);
        $this->assertNotContains($completedPpjb->id, $targets);
        $this->assertSame(['ppjb_system_code_missing', 'ppjb_system_code_missing', 'ppjb_system_code_missing', 'sp3k_system_code_missing'], array_map(fn (RepairIssue $issue): string => $issue->issueCode, $issues));
    }

    public function test_sp3k_preview_contains_exact_read_only_plan_and_stable_fingerprint(): void
    {
        $this->seed();
        $branch = Branch::factory()->create(['code' => 'MGL']);
        $case = $this->caseFor($branch);
        $process = $this->sp3kFor($case, ['sp3k_number' => 'LEGACY-SP3K', 'sp3k_date' => '2026-09-20']);
        $engine = app(TransactionRepairEngine::class);

        $plan = $engine->plan($this->issueFor($engine, $branch, 'sp3k_system_code_missing'));
        $samePlan = $engine->plan($this->issueFor($engine, $branch, 'sp3k_system_code_missing'));

        $this->assertSame($case->id, $plan->issue->salesCaseId);
        $this->assertSame($process->id, $plan->issue->targetId);
        $this->assertSame('sp3k_system_code_missing', $plan->issue->issueCode);
        $this->assertSame('GENERATE_SP3K_SYSTEM_CODE', $plan->actionCode);
        $this->assertSame('AUTO_FIXABLE', $plan->issue->repairability->value);
        $this->assertSame(['sales_case_id' => $case->id, 'sp3k_code' => null, 'sp3k_number' => 'LEGACY-SP3K', 'sp3k_date' => '2026-09-20', 'is_authoritative' => true, 'response_type' => BankResponseType::Approved->value, 'bank_id' => $process->bank_id, 'document_submission_id' => $process->document_submission_id], $plan->before);
        $this->assertSame(['sp3k_code' => 'SYSTEM GENERATED', 'format' => 'SP3K-{BRANCH}-{YEAR}-{SEQUENCE}'], $plan->proposed);
        $this->assertSame($plan->fingerprint, $samePlan->fingerprint);
        $this->assertSame(0, BusinessNumberSequence::query()->count());
        $this->assertSame(0, RepairAction::query()->count());
    }

    public function test_ppjb_preview_contains_exact_read_only_plan_and_stable_fingerprint(): void
    {
        $this->seed();
        $branch = Branch::factory()->create(['code' => 'MGL']);
        $case = $this->caseFor($branch);
        $ppjb = DeveloperPpjb::factory()->create(['sales_case_id' => $case->id, 'document_date' => '2026-09-21', 'document_number' => 'PPJB-LEGACY', 'status' => DeveloperPpjbStatus::Active]);
        $engine = app(TransactionRepairEngine::class);

        $plan = $engine->plan($this->issueFor($engine, $branch, 'ppjb_system_code_missing'));
        $samePlan = $engine->plan($this->issueFor($engine, $branch, 'ppjb_system_code_missing'));

        $this->assertSame($case->id, $plan->issue->salesCaseId);
        $this->assertSame($ppjb->id, $plan->issue->targetId);
        $this->assertSame('ppjb_system_code_missing', $plan->issue->issueCode);
        $this->assertSame('GENERATE_PPJB_SYSTEM_CODE', $plan->actionCode);
        $this->assertSame('AUTO_FIXABLE', $plan->issue->repairability->value);
        $this->assertSame(['ppjb_code' => null, 'document_number' => 'PPJB-LEGACY', 'document_date' => '2026-09-21', 'status' => DeveloperPpjbStatus::Active->value, 'bank_process_id' => null], $plan->before);
        $this->assertSame(['ppjb_code' => 'SYSTEM GENERATED', 'format' => 'PPJB-{BRANCH}-{YEAR}-{SEQUENCE}'], $plan->proposed);
        $this->assertSame($plan->fingerprint, $samePlan->fingerprint);
        $this->assertSame(0, BusinessNumberSequence::query()->count());
        $this->assertSame(0, RepairAction::query()->count());
    }

    #[DataProvider('sp3kProtectedFacts')]
    public function test_sp3k_apply_rejects_each_stale_protected_fact(string $field, mixed $value): void
    {
        $this->seed();
        $branch = Branch::factory()->create(['code' => 'MGL']);
        $case = $this->caseFor($branch);
        $process = $this->sp3kFor($case);
        $engine = app(TransactionRepairEngine::class);
        $plan = $engine->plan($this->issueFor($engine, $branch, 'sp3k_system_code_missing'));
        $process->update([$field => $value]);
        $user = $this->userWithRole(UserRole::HqAdmin);
        $exception = null;

        try {
            $engine->apply($user, $plan);
        } catch (ValidationException $exception) {
        }

        $this->assertInstanceOf(ValidationException::class, $exception);
        $this->assertNull($process->refresh()->sp3k_code);
        $this->assertSame(0, BusinessNumberSequence::query()->count());
        $this->assertSame(0, RepairAction::query()->count());
    }

    public static function sp3kProtectedFacts(): array
    {
        return [
            'sp3k date' => ['sp3k_date', '2026-09-21'],
            'sp3k number' => ['sp3k_number', 'CHANGED-SP3K'],
            'response type' => ['response_type', BankResponseType::Revision],
            'bank id' => ['bank_id', null],
            'document submission id' => ['document_submission_id', null],
        ];
    }

    #[DataProvider('ppjbProtectedFacts')]
    public function test_ppjb_apply_rejects_each_stale_protected_fact(string $field, mixed $value): void
    {
        $this->seed();
        $branch = Branch::factory()->create(['code' => 'MGL']);
        $case = $this->caseFor($branch);
        $ppjb = DeveloperPpjb::factory()->create(['sales_case_id' => $case->id]);
        $engine = app(TransactionRepairEngine::class);
        $plan = $engine->plan($this->issueFor($engine, $branch, 'ppjb_system_code_missing'));
        if ($field === 'bank_process_id') {
            $value = $this->sp3kFor($case)->id;
        }
        $ppjb->update([$field => $value]);
        $user = $this->userWithRole(UserRole::HqAdmin);
        $exception = null;

        try {
            $engine->apply($user, $plan);
        } catch (ValidationException $exception) {
        }

        $this->assertInstanceOf(ValidationException::class, $exception);
        $this->assertNull($ppjb->refresh()->ppjb_code);
        $this->assertSame(0, BusinessNumberSequence::query()->count());
        $this->assertSame(0, RepairAction::query()->count());
    }

    public static function ppjbProtectedFacts(): array
    {
        return [
            'document date' => ['document_date', '2026-09-22'],
            'status' => ['status', DeveloperPpjbStatus::Superseded],
            'document number' => ['document_number', 'CHANGED-PPJB'],
            'bank process id' => ['bank_process_id', '01j00000000000000000000000'],
        ];
    }

    public function test_ppjb_bank_process_fingerprint_mutation_rejects_stale_plan(): void
    {
        $this->seed();
        $branch = Branch::factory()->create(['code' => 'MGL']);
        $case = $this->caseFor($branch);
        $ppjb = DeveloperPpjb::factory()->create(['sales_case_id' => $case->id]);
        $process = $this->sp3kFor($case);
        $engine = app(TransactionRepairEngine::class);
        $plan = $engine->plan($this->issueFor($engine, $branch, 'ppjb_system_code_missing'));
        $ppjb->update(['bank_process_id' => $process->id]);
        $user = $this->userWithRole(UserRole::HqAdmin);
        $exception = null;

        try {
            $engine->apply($user, $plan);
        } catch (ValidationException $exception) {
        }

        $this->assertInstanceOf(ValidationException::class, $exception);
        $this->assertNull($ppjb->refresh()->ppjb_code);
        $this->assertSame(0, BusinessNumberSequence::query()->count());
        $this->assertSame(0, RepairAction::query()->count());
    }

    public function test_old_sp3k_plan_rejects_after_code_generated_elsewhere_without_second_allocation(): void
    {
        $this->seed();
        $branch = Branch::factory()->create(['code' => 'MGL']);
        $case = $this->caseFor($branch);
        $process = $this->sp3kFor($case);
        $engine = app(TransactionRepairEngine::class);
        $plan = $engine->plan($this->issueFor($engine, $branch, 'sp3k_system_code_missing'));
        $generated = app(BusinessNumberGenerator::class)->ensureSp3kCode($process);
        $user = $this->userWithRole(UserRole::HqAdmin);
        $exception = null;

        try {
            $engine->apply($user, $plan);
        } catch (ValidationException $exception) {
        }

        $this->assertSame('SP3K-MGL-2026-000001', $generated);
        $this->assertInstanceOf(ValidationException::class, $exception);
        $this->assertSame($generated, $process->refresh()->sp3k_code);
        $this->assertSame(1, BusinessNumberSequence::query()->count());
        $this->assertSame(1, BusinessNumberSequence::query()->value('last_number'));
        $this->assertSame(0, RepairAction::query()->count());
        $this->assertNotContains($process->id, array_map(fn (RepairIssue $issue): string => $issue->targetId, $engine->scanBranch($branch)));
    }

    public function test_old_ppjb_plan_rejects_after_code_generated_elsewhere_without_second_allocation(): void
    {
        $this->seed();
        $branch = Branch::factory()->create(['code' => 'MGL']);
        $case = $this->caseFor($branch);
        $ppjb = DeveloperPpjb::factory()->create(['sales_case_id' => $case->id]);
        $engine = app(TransactionRepairEngine::class);
        $plan = $engine->plan($this->issueFor($engine, $branch, 'ppjb_system_code_missing'));
        $generated = app(BusinessNumberGenerator::class)->ensurePpjbCode($ppjb);
        $user = $this->userWithRole(UserRole::HqAdmin);
        $exception = null;

        try {
            $engine->apply($user, $plan);
        } catch (ValidationException $exception) {
        }

        $this->assertSame('PPJB-MGL-2026-000001', $generated);
        $this->assertInstanceOf(ValidationException::class, $exception);
        $this->assertSame($generated, $ppjb->refresh()->ppjb_code);
        $this->assertSame(1, BusinessNumberSequence::query()->count());
        $this->assertSame(1, BusinessNumberSequence::query()->value('last_number'));
        $this->assertSame(0, RepairAction::query()->count());
        $this->assertNotContains($ppjb->id, array_map(fn (RepairIssue $issue): string => $issue->targetId, $engine->scanBranch($branch)));
    }

    public function test_successful_sp3k_apply_preserves_business_facts_and_records_complete_audit(): void
    {
        $this->seed();
        $branch = Branch::factory()->create(['code' => 'MGL']);
        $case = $this->caseFor($branch);
        $process = $this->sp3kFor($case, ['sp3k_number' => 'LEGACY-SP3K', 'sp3k_date' => '2026-09-20']);
        $before = ['sales_case_id' => $case->id, 'sp3k_code' => null, 'sp3k_number' => 'LEGACY-SP3K', 'sp3k_date' => '2026-09-20', 'is_authoritative' => true, 'response_type' => BankResponseType::Approved->value, 'bank_id' => $process->bank_id, 'document_submission_id' => $process->document_submission_id];
        $engine = app(TransactionRepairEngine::class);
        $plan = $engine->plan($this->issueFor($engine, $branch, 'sp3k_system_code_missing'));
        $user = $this->userWithRole(UserRole::HqAdmin);

        $result = $engine->apply($user, $plan);
        $process->refresh();
        $audit = RepairAction::query()->firstOrFail();
        $sequence = BusinessNumberSequence::query()->firstOrFail();

        $this->assertTrue($result->verified);
        $this->assertSame('SP3K-MGL-2026-000001', $process->sp3k_code);
        $this->assertSame('LEGACY-SP3K', $process->sp3k_number);
        $this->assertSame('2026-09-20', $process->sp3k_date->toDateString());
        $this->assertTrue($process->is_authoritative);
        $this->assertSame(BankResponseType::Approved, $process->response_type);
        $this->assertSame($before['bank_id'], $process->bank_id);
        $this->assertSame($before['document_submission_id'], $process->document_submission_id);
        $this->assertSame($before, $result->before);
        $this->assertSame($process->sp3k_code, $result->after['sp3k_code']);
        $this->assertSame(SalesCaseStage::DataKonsumen->value, $result->stageBefore);
        $this->assertSame(SalesCaseStage::PpjbDev->value, $result->stageAfter);
        $this->assertSame(UnitStatus::Tersedia->value, $result->unitStatusBefore);
        $this->assertSame(UnitStatus::Booking->value, $result->unitStatusAfter);
        $this->assertSame(1, RepairAction::query()->count());
        $this->assertSame($case->branch_id, $audit->branch_id);
        $this->assertSame($case->id, $audit->sales_case_id);
        $this->assertSame('sp3k_system_code_missing', $audit->issue_code);
        $this->assertSame(BankProcess::class, $audit->target_type);
        $this->assertSame($process->id, $audit->target_id);
        $this->assertSame('GENERATE_SP3K_SYSTEM_CODE', $audit->action_code);
        $this->assertSame($user->id, $audit->performed_by);
        $this->assertSame($plan->fingerprint, $audit->plan_fingerprint);
        $this->assertSame($before, $audit->before_payload);
        $this->assertSame($result->after, $audit->after_payload);
        $this->assertSame(['is_authoritative' => true, 'sp3k_date' => '2026-09-20', 'sp3k_code' => null], $audit->evidence_payload);
        $auditJson = json_encode($audit->toArray(), JSON_THROW_ON_ERROR);
        $this->assertStringNotContainsString($case->consumer->name, $auditJson);
        $this->assertStringNotContainsString($case->consumer->nik, $auditJson);
        $this->assertStringNotContainsString($case->consumer->phone, $auditJson);
        $this->assertSame(BusinessNumberType::Sp3k, $sequence->type);
        $this->assertSame($branch->id, $sequence->branch_id);
        $this->assertSame(2026, $sequence->year);
        $this->assertSame(1, $sequence->last_number);
        $this->assertCount(0, $engine->scanBranch($branch));
    }

    public function test_successful_ppjb_apply_preserves_business_facts_and_records_complete_audit(): void
    {
        $this->seed();
        $branch = Branch::factory()->create(['code' => 'MGL']);
        $case = $this->caseFor($branch);
        $process = $this->sp3kFor($case);
        $ppjb = DeveloperPpjb::factory()->create(['sales_case_id' => $case->id, 'bank_process_id' => $process->id, 'document_date' => '2026-09-21', 'document_number' => 'LEGACY-PPJB', 'status' => DeveloperPpjbStatus::Active]);
        $before = ['ppjb_code' => null, 'document_number' => 'LEGACY-PPJB', 'document_date' => '2026-09-21', 'status' => DeveloperPpjbStatus::Active->value, 'bank_process_id' => $process->id];
        $engine = app(TransactionRepairEngine::class);
        $plan = $engine->plan($this->issueFor($engine, $branch, 'ppjb_system_code_missing'));
        $user = $this->userWithRole(UserRole::HqAdmin);

        $result = $engine->apply($user, $plan);
        $ppjb->refresh();
        $audit = RepairAction::query()->firstOrFail();

        $this->assertTrue($result->verified);
        $this->assertSame('PPJB-MGL-2026-000001', $ppjb->ppjb_code);
        $this->assertSame('LEGACY-PPJB', $ppjb->document_number);
        $this->assertSame('2026-09-21', $ppjb->document_date->toDateString());
        $this->assertSame(DeveloperPpjbStatus::Active, $ppjb->status);
        $this->assertSame($process->id, $ppjb->bank_process_id);
        $this->assertSame($before, $result->before);
        $this->assertSame($ppjb->ppjb_code, $result->after['ppjb_code']);
        $this->assertSame(SalesCaseStage::DataKonsumen->value, $result->stageBefore);
        $this->assertSame(SalesCaseStage::Akad->value, $result->stageAfter);
        $this->assertSame(UnitStatus::Tersedia->value, $result->unitStatusBefore);
        $this->assertSame(UnitStatus::Booking->value, $result->unitStatusAfter);
        $this->assertSame(1, RepairAction::query()->count());
        $this->assertSame($user->id, $audit->performed_by);
        $this->assertSame($plan->fingerprint, $audit->plan_fingerprint);
        $this->assertSame($before, $audit->before_payload);
        $this->assertSame($result->after, $audit->after_payload);
        $this->assertSame(['document_date' => '2026-09-21', 'status' => DeveloperPpjbStatus::Active->value, 'ppjb_code' => null], $audit->evidence_payload);
        $this->assertSame(1, BusinessNumberSequence::query()->count());
        $this->assertSame(1, BusinessNumberSequence::query()->value('last_number'));
        $this->assertCount(1, $engine->scanBranch($branch));
    }

    public function test_apply_authorization_matrix_uses_isolated_targets(): void
    {
        $this->seed();
        $engine = app(TransactionRepairEngine::class);
        $cases = [
            [UserRole::HqAdmin, 'HQM', true],
            [UserRole::BranchAdmin, 'MGL', true],
            [UserRole::BranchAdmin, 'SMG', false],
            [UserRole::BranchManager, 'MGL2', false],
            [UserRole::Auditor, 'MGL3', false],
        ];

        foreach ($cases as [$role, $userBranchCode, $allowed]) {
            $branch = Branch::factory()->create(['code' => $userBranchCode]);
            $userBranch = $allowed && $role === UserRole::HqAdmin ? null : ($role === UserRole::BranchAdmin && $userBranchCode === 'SMG' ? Branch::factory()->create(['code' => 'MGL4']) : $branch);
            $case = $this->caseFor($branch);
            $process = $this->sp3kFor($case);
            $user = $this->userWithRole($role, $userBranch);
            $plan = $engine->plan($this->issueFor($engine, $branch, 'sp3k_system_code_missing'));
            $exception = null;

            try {
                $engine->apply($user, $plan);
            } catch (AuthorizationException $exception) {
            }

            if ($allowed) {
                $this->assertNull($exception);
                $this->assertNotNull($process->refresh()->sp3k_code);
            } else {
                $this->assertInstanceOf(AuthorizationException::class, $exception);
                $this->assertNull($process->refresh()->sp3k_code);
                $this->assertSame(0, BusinessNumberSequence::query()->count());
                $this->assertSame(0, RepairAction::query()->count());
            }

            RepairAction::query()->delete();
            BusinessNumberSequence::query()->delete();
        }
    }

    public function test_stale_plan_is_rejected_without_sequence_audit_or_canonical_code(): void
    {
        $this->seed();
        $branch = Branch::factory()->create(['code' => 'MGL']);
        $case = $this->caseFor($branch);
        $process = $this->sp3kFor($case);
        $engine = app(TransactionRepairEngine::class);
        $plan = $engine->plan($this->issueFor($engine, $branch, 'sp3k_system_code_missing'));
        $process->update(['sp3k_date' => '2026-09-21']);
        $user = $this->userWithRole(UserRole::HqAdmin);
        $exception = null;

        try {
            $engine->apply($user, $plan);
        } catch (ValidationException $exception) {
        }

        $this->assertInstanceOf(ValidationException::class, $exception);
        $this->assertSame(0, RepairAction::query()->count());
        $this->assertSame(0, BusinessNumberSequence::query()->count());
        $this->assertNull($process->refresh()->sp3k_code);
    }

    public function test_crafted_plan_cannot_cross_sales_case_or_use_fake_action(): void
    {
        $this->seed();
        $branch = Branch::factory()->create(['code' => 'MGL']);
        $caseA = $this->caseFor($branch);
        $caseB = $this->caseFor($branch);
        $target = $this->sp3kFor($caseB);
        $user = $this->userWithRole(UserRole::HqAdmin);
        $engine = app(TransactionRepairEngine::class);
        $realIssue = $this->issueFor($engine, $branch, 'sp3k_system_code_missing');
        $forgedIssue = new RepairIssue($realIssue->issueCode, $caseA->id, $caseA->branch_id, BankProcess::class, $target->id, Repairability::AutoFixable, 'forged', $realIssue->evidence);
        $plan = new RepairPlan($forgedIssue, 'GENERATE_SP3K_SYSTEM_CODE', ['sp3k_code' => null], ['sp3k_code' => 'SYSTEM GENERATED'], hash('sha256', 'forged'));
        $exception = null;

        try {
            $engine->apply($user, $plan);
        } catch (ValidationException $exception) {
        }

        $this->assertInstanceOf(ValidationException::class, $exception);
        $this->assertStringContainsString('does not belong', $exception->getMessage());
        $this->assertNull($target->refresh()->sp3k_code);
        $this->assertSame(0, BusinessNumberSequence::query()->count());
        $this->assertSame(0, RepairAction::query()->count());

        $realPlan = $engine->plan($realIssue);
        $fakeAction = new RepairPlan($realPlan->issue, 'SOMETHING_FAKE', $realPlan->before, $realPlan->proposed, $realPlan->fingerprint);
        $exception = null;

        try {
            $engine->apply($user, $fakeAction);
        } catch (ValidationException $exception) {
        }

        $this->assertInstanceOf(ValidationException::class, $exception);
        $this->assertSame(0, BusinessNumberSequence::query()->count());
        $this->assertSame(0, RepairAction::query()->count());
        $this->assertNull($target->refresh()->sp3k_code);
    }

    public function test_atomic_rollback_after_number_allocation_restores_all_state_and_next_valid_repair_starts_at_one(): void
    {
        $this->seed();
        $branch = Branch::factory()->create(['code' => 'MGL']);
        $case = $this->caseFor($branch);
        $process = $this->sp3kFor($case);
        $engine = app(TransactionRepairEngine::class);
        $plan = $engine->plan($this->issueFor($engine, $branch, 'sp3k_system_code_missing'));
        $user = $this->userWithRole(UserRole::HqAdmin);
        $failureArmed = true;
        DB::listen(function (QueryExecuted $query) use (&$failureArmed): void {
            if ($failureArmed && str_contains(strtolower($query->sql), 'insert into "repair_actions"')) {
                $failureArmed = false;
                throw ValidationException::withMessages(['repair' => 'Test-only failure after allocation.']);
            }
        });
        $exception = null;

        try {
            app(TransactionRepairEngine::class)->apply($user, $plan);
        } catch (ValidationException $exception) {
        }

        $this->assertInstanceOf(ValidationException::class, $exception);
        $this->assertNull($process->refresh()->sp3k_code);
        $this->assertSame(0, BusinessNumberSequence::query()->count());
        $this->assertSame(0, RepairAction::query()->count());
        $this->assertSame(SalesCaseStage::DataKonsumen, $case->refresh()->current_stage);
        $this->assertSame(UnitStatus::Tersedia, $case->unit->refresh()->status);

        $this->app->instance(BusinessNumberGenerator::class, new BusinessNumberGenerator);
        $result = app(TransactionRepairEngine::class)->apply($user, $plan);

        $this->assertTrue($result->verified);
        $this->assertSame('SP3K-MGL-2026-000001', $process->refresh()->sp3k_code);
        $this->assertSame(1, BusinessNumberSequence::query()->value('last_number'));
        $this->assertSame(1, RepairAction::query()->count());
    }

    #[DataProvider('nonAutoRepairabilities')]
    public function test_non_auto_fixable_plan_is_rejected_without_mutation(string $repairability): void
    {
        $this->seed();
        $branch = Branch::factory()->create(['code' => 'MGL']);
        $case = $this->caseFor($branch);
        $process = $this->sp3kFor($case);
        $engine = app(TransactionRepairEngine::class);
        $issue = $this->issueFor($engine, $branch, 'sp3k_system_code_missing');
        $nonAutoIssue = new RepairIssue($issue->issueCode, $issue->salesCaseId, $issue->branchId, $issue->targetType, $issue->targetId, Repairability::from($repairability), $issue->diagnosis, $issue->evidence);
        $plan = new RepairPlan($nonAutoIssue, 'GENERATE_SP3K_SYSTEM_CODE', $engine->plan($issue)->before, $engine->plan($issue)->proposed, $engine->plan($issue)->fingerprint);
        $exception = null;

        try {
            $engine->apply($this->userWithRole(UserRole::HqAdmin), $plan);
        } catch (ValidationException $exception) {
        }

        $this->assertInstanceOf(ValidationException::class, $exception);
        $this->assertNull($process->refresh()->sp3k_code);
        $this->assertSame(0, BusinessNumberSequence::query()->count());
        $this->assertSame(0, RepairAction::query()->count());
    }

    public static function nonAutoRepairabilities(): array
    {
        return [
            'review required' => [Repairability::ReviewRequired->value],
            'manual decision required' => [Repairability::ManualDecisionRequired->value],
        ];
    }

    public function test_unknown_rule_or_target_type_is_rejected_without_mutation(): void
    {
        $this->seed();
        $branch = Branch::factory()->create(['code' => 'MGL']);
        $case = $this->caseFor($branch);
        $process = $this->sp3kFor($case);
        $engine = app(TransactionRepairEngine::class);
        $user = $this->userWithRole(UserRole::HqAdmin);
        $issue = $this->issueFor($engine, $branch, 'sp3k_system_code_missing');
        $unknown = new RepairIssue('unknown_rule', $case->id, $branch->id, BankProcess::class, $process->id, Repairability::AutoFixable, 'unknown', []);
        $wrongTarget = new RepairIssue($issue->issueCode, $case->id, $branch->id, Project::class, $process->id, Repairability::AutoFixable, 'wrong target', []);

        $unknownException = null;
        try {
            $engine->plan($unknown);
        } catch (ValidationException $unknownException) {
        }
        $wrongTargetException = null;
        try {
            $engine->apply($user, new RepairPlan($wrongTarget, 'GENERATE_SP3K_SYSTEM_CODE', [], [], 'forged-fingerprint'));
        } catch (ValidationException $wrongTargetException) {
        }

        $this->assertInstanceOf(ValidationException::class, $unknownException);
        $this->assertInstanceOf(ValidationException::class, $wrongTargetException);
        $this->assertNull($process->refresh()->sp3k_code);
        $this->assertSame(0, BusinessNumberSequence::query()->count());
        $this->assertSame(0, RepairAction::query()->count());
    }

    public function test_scan_command_is_complete_deterministic_branch_scoped_and_read_only(): void
    {
        $this->seed();
        $branch = Branch::factory()->create(['code' => 'MGL']);
        $otherBranch = Branch::factory()->create(['code' => 'SMG']);
        $case = $this->caseFor($branch);
        $otherCase = $this->caseFor($otherBranch);
        $case->consumer->update(['name' => 'SECRET_NAME', 'nik' => 'SECRET_NIK', 'phone' => 'SECRET_PHONE']);
        $sp3k = $this->sp3kFor($case);
        $ppjb = DeveloperPpjb::factory()->create(['sales_case_id' => $case->id]);
        $otherSp3k = $this->sp3kFor($otherCase);

        $this->assertSame(1, Artisan::call('oasis:repair-scan'));
        $this->assertSame(1, Artisan::call('oasis:repair-scan', ['--branch-id' => 'missing', '--json' => true]));
        $this->assertSame(0, Artisan::call('oasis:repair-scan', ['--branch-id' => $branch->id, '--json' => true]));
        $first = Artisan::output();
        $this->assertSame(0, Artisan::call('oasis:repair-scan', ['--branch-id' => $branch->id, '--json' => true]));
        $second = Artisan::output();
        $payload = json_decode($second, true, 512, JSON_THROW_ON_ERROR);
        $firstPayload = json_decode($first, true, 512, JSON_THROW_ON_ERROR);

        $this->assertSame($first, $second);
        $this->assertSame($branch->id, $payload['branch']['id']);
        $this->assertSame(['ppjb_system_code_missing' => 1, 'sp3k_system_code_missing' => 1], $payload['counts']);
        $this->assertSame(['AUTO_FIXABLE' => 2], $payload['repairability_counts']);
        $this->assertSame($firstPayload, $payload);
        $this->assertSame([$ppjb->id, $sp3k->id], array_column($payload['issues'], 'target_id'));
        $this->assertNotContains($otherSp3k->id, array_column($payload['issues'], 'target_id'));
        $this->assertStringNotContainsString('SECRET_NAME', json_encode($payload, JSON_THROW_ON_ERROR));
        $this->assertStringNotContainsString('SECRET_NIK', json_encode($payload, JSON_THROW_ON_ERROR));
        $this->assertStringNotContainsString('SECRET_PHONE', json_encode($payload, JSON_THROW_ON_ERROR));
        $this->assertNull($sp3k->refresh()->sp3k_code);
        $this->assertNull($ppjb->refresh()->ppjb_code);
        $this->assertNull($otherSp3k->refresh()->sp3k_code);
        $this->assertSame(0, BusinessNumberSequence::query()->count());
        $this->assertSame(0, RepairAction::query()->count());
    }

    private function caseFor(Branch $branch): SalesCase
    {
        $unit = Unit::factory()->for(Project::factory()->for($branch))->create();

        return SalesCase::factory()->forUnit($unit)->create();
    }

    private function sp3kFor(SalesCase $case, array $attributes = []): BankProcess
    {
        return BankProcess::factory()->create(array_merge([
            'sales_case_id' => $case->id,
            'is_authoritative' => true,
            'response_type' => BankResponseType::Approved,
            'response_date' => '2026-09-20',
            'sp3k_date' => '2026-09-20',
        ], $attributes));
    }

    private function userWithRole(UserRole $role, ?Branch $branch = null): User
    {
        $user = User::factory()->create(['branch_id' => $branch?->id]);
        $user->assignRole($role);

        return $user;
    }

    private function issueFor(TransactionRepairEngine $engine, Branch $branch, string $issueCode): RepairIssue
    {
        foreach ($engine->scanBranch($branch) as $issue) {
            if ($issue->issueCode === $issueCode) {
                return $issue;
            }
        }

        throw new ValidationException(['issue' => 'Expected test issue was not detected.']);
    }
}
