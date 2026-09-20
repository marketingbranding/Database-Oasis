<?php

namespace Tests\Feature;

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
use App\Services\Repair\RepairIssue;
use App\Services\Repair\RepairPlan;
use App\Services\TransactionRepairEngine;
use App\UserRole;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class TransactionRepairEngineTest extends TestCase
{
    use RefreshDatabase;

    public function test_rule_detection_covers_sp3k_and_each_ppjb_target(): void
    {
        $this->seed();
        $branch = Branch::factory()->create(['code' => 'MGL']);
        $case = SalesCase::factory()->forUnit(Unit::factory()->for(Project::factory()->for($branch))->create())->create();
        $engine = app(TransactionRepairEngine::class);
        $sp3k = BankProcess::factory()->create(['sales_case_id' => $case->id, 'is_authoritative' => true, 'sp3k_date' => '2026-09-20']);
        $active = DeveloperPpjb::factory()->create(['sales_case_id' => $case->id, 'status' => 'ACTIVE']);
        $superseded = DeveloperPpjb::factory()->create(['sales_case_id' => $case->id, 'status' => 'SUPERSEDED']);
        $cancelled = DeveloperPpjb::factory()->create(['sales_case_id' => $case->id, 'status' => 'CANCELLED']);

        $issues = $engine->scanBranch($branch);
        $targets = array_map(fn ($issue): string => $issue->targetId, $issues);
        $this->assertContains($sp3k->id, $targets);
        $this->assertContains($active->id, $targets);
        $this->assertContains($superseded->id, $targets);
        $this->assertContains($cancelled->id, $targets);
        $this->assertSame([], array_diff($targets, [$sp3k->id, $active->id, $superseded->id, $cancelled->id]));

        $sp3k->update(['is_authoritative' => false]);
        $this->assertNotContains($sp3k->id, array_map(fn ($issue): string => $issue->targetId, $engine->scanBranch($branch)));
        $sp3k->update(['is_authoritative' => true, 'sp3k_date' => null]);
        $this->assertNotContains($sp3k->id, array_map(fn ($issue): string => $issue->targetId, $engine->scanBranch($branch)));
    }

    public function test_preview_is_read_only_and_contains_rule_owned_plan_facts(): void
    {
        $this->seed();
        $branch = Branch::factory()->create(['code' => 'MGL']);
        $case = SalesCase::factory()->forUnit(Unit::factory()->for(Project::factory()->for($branch))->create())->create();
        $process = BankProcess::factory()->create(['sales_case_id' => $case->id, 'is_authoritative' => true, 'sp3k_date' => '2026-09-20', 'sp3k_number' => 'LEGACY']);
        $plan = app(TransactionRepairEngine::class)->plan(app(TransactionRepairEngine::class)->scanBranch($branch)[0]);

        $this->assertSame('GENERATE_SP3K_SYSTEM_CODE', $plan->actionCode);
        $this->assertSame('AUTO_FIXABLE', $plan->issue->repairability->value);
        $this->assertSame($case->id, $plan->issue->salesCaseId);
        $this->assertSame($process->id, $plan->issue->targetId);
        $this->assertSame(['sales_case_id' => $case->id, 'sp3k_code' => null, 'sp3k_number' => 'LEGACY', 'sp3k_date' => '2026-09-20', 'is_authoritative' => true, 'response_type' => $process->response_type->value, 'bank_id' => $process->bank_id, 'document_submission_id' => $process->document_submission_id], $plan->before);
        $this->assertSame(0, BusinessNumberSequence::query()->count());
        $this->assertSame(0, RepairAction::query()->count());
    }

    public function test_fingerprint_changes_when_protected_sp3k_fact_changes(): void
    {
        $this->seed();
        $branch = Branch::factory()->create();
        $case = SalesCase::factory()->forUnit(Unit::factory()->for(Project::factory()->for($branch))->create())->create();
        $process = BankProcess::factory()->create(['sales_case_id' => $case->id, 'is_authoritative' => true, 'sp3k_date' => '2026-09-20']);
        $engine = app(TransactionRepairEngine::class);
        $plan = $engine->plan($engine->scanBranch($branch)[0]);
        $process->update(['sp3k_date' => '2026-09-21']);
        $user = User::factory()->create();
        $user->assignRole(UserRole::HqAdmin);

        $this->expectException(ValidationException::class);
        $engine->apply($user, $plan);
        $this->assertSame(0, RepairAction::query()->count());
        $this->assertSame(0, BusinessNumberSequence::query()->count());
    }

    public function test_scan_is_branch_scoped_and_pii_free(): void
    {
        $this->seed();
        $branchA = Branch::factory()->create();
        $branchB = Branch::factory()->create();
        $caseA = SalesCase::factory()->forUnit(Unit::factory()->for(Project::factory()->for($branchA))->create())->create();
        $caseB = SalesCase::factory()->forUnit(Unit::factory()->for(Project::factory()->for($branchB))->create())->create();
        $caseA->consumer->update(['name' => 'SECRET_NAME', 'nik' => 'SECRET_NIK', 'phone' => 'SECRET_PHONE']);
        BankProcess::factory()->create(['sales_case_id' => $caseA->id, 'is_authoritative' => true, 'sp3k_date' => '2026-09-20']);
        BankProcess::factory()->create(['sales_case_id' => $caseB->id, 'is_authoritative' => true, 'sp3k_date' => '2026-09-20']);
        $issues = app(TransactionRepairEngine::class)->scanBranch($branchA);
        $json = json_encode(array_map(fn ($issue): array => $issue->toArray(), $issues), JSON_THROW_ON_ERROR);
        $this->assertStringNotContainsString('SECRET_NAME', $json);
        $this->assertStringNotContainsString('SECRET_NIK', $json);
        $this->assertStringNotContainsString('SECRET_PHONE', $json);
        $this->assertContains($caseA->id, array_map(fn ($issue): string => $issue->salesCaseId, $issues));
        $this->assertNotContains($caseB->id, array_map(fn ($issue): string => $issue->salesCaseId, $issues));
        $this->assertSame(0, BusinessNumberSequence::query()->count());
        $this->assertSame(0, RepairAction::query()->count());
    }

    public function test_scan_plan_apply_and_audit_are_safe_and_idempotent(): void
    {
        $this->seed();
        $branch = Branch::factory()->create(['code' => 'MGL']);
        $unit = Unit::factory()->for(Project::factory()->for($branch))->create();
        $case = SalesCase::factory()->forUnit($unit)->create();
        $process = BankProcess::factory()->create(['sales_case_id' => $case->id, 'is_authoritative' => true, 'sp3k_date' => '2026-09-20']);
        $ppjb = DeveloperPpjb::factory()->create(['sales_case_id' => $case->id]);
        $user = User::factory()->create();
        $user->assignRole(UserRole::HqAdmin);
        $engine = app(TransactionRepairEngine::class);

        $issues = $engine->scanBranch($branch);
        $this->assertCount(2, $issues);
        $plan = $engine->plan($issues[0]);
        $this->assertSame(0, BusinessNumberSequence::query()->count());
        $result = $engine->apply($user, $plan);

        $this->assertNotNull($result->after);
        $this->assertSame(1, RepairAction::query()->count());
        $this->assertNotNull($process->refresh()->sp3k_code ?? $ppjb->refresh()->ppjb_code);
        $this->assertCount(1, $engine->scanBranch($branch));
    }

    public function test_apply_authorization_matrix(): void
    {
        $this->seed();
        $branch = Branch::factory()->create(['code' => 'MGL']);
        $case = SalesCase::factory()->forUnit(Unit::factory()->for(Project::factory()->for($branch))->create())->create();
        BankProcess::factory()->create(['sales_case_id' => $case->id, 'is_authoritative' => true, 'sp3k_date' => '2026-09-20']);
        $engine = app(TransactionRepairEngine::class);
        $makePlan = fn (): RepairPlan => $engine->plan($engine->scanBranch($branch)[0]);

        $hq = User::factory()->create();
        $hq->assignRole(UserRole::HqAdmin);
        $branchAdmin = User::factory()->create(['branch_id' => $branch->id]);
        $branchAdmin->assignRole(UserRole::BranchAdmin);
        $manager = User::factory()->create(['branch_id' => $branch->id]);
        $manager->assignRole(UserRole::BranchManager);
        $auditor = User::factory()->create(['branch_id' => $branch->id]);
        $auditor->assignRole(UserRole::Auditor);
        $plan = $makePlan();
        $this->assertNotNull($engine->apply($branchAdmin, $plan));
        foreach ([$manager, $auditor] as $denied) {
            try {
                $engine->apply($denied, $plan);
                $this->fail('Unauthorized repair unexpectedly succeeded.');
            } catch (AuthorizationException) {
                $this->assertSame(1, RepairAction::query()->count());
            }
        }
    }

    public function test_stale_plan_is_rejected_without_sequence_or_audit(): void
    {
        $this->seed();
        $branch = Branch::factory()->create(['code' => 'MGL']);
        $case = SalesCase::factory()->forUnit(Unit::factory()->for(Project::factory()->for($branch))->create())->create();
        $process = BankProcess::factory()->create(['sales_case_id' => $case->id, 'is_authoritative' => true, 'sp3k_date' => '2026-09-20']);
        $engine = app(TransactionRepairEngine::class);
        $plan = $engine->plan($engine->scanBranch($branch)[0]);
        $process->update(['sp3k_date' => '2026-09-21']);
        $user = User::factory()->create();
        $user->assignRole(UserRole::HqAdmin);

        try {
            $engine->apply($user, $plan);
            $this->fail('Stale plan unexpectedly applied.');
        } catch (ValidationException) {
            $this->assertSame(0, RepairAction::query()->count());
            $this->assertSame(0, BusinessNumberSequence::query()->count());
        }
    }

    public function test_crafted_plan_cannot_cross_sales_case_or_fake_action(): void
    {
        $this->seed();
        $branch = Branch::factory()->create(['code' => 'MGL']);
        $caseA = SalesCase::factory()->forUnit(Unit::factory()->for(Project::factory()->for($branch))->create())->create();
        $caseB = SalesCase::factory()->forUnit(Unit::factory()->for(Project::factory()->for($branch))->create())->create();
        $target = BankProcess::factory()->create(['sales_case_id' => $caseB->id, 'is_authoritative' => true, 'sp3k_date' => '2026-09-20']);
        $user = User::factory()->create();
        $user->assignRole(UserRole::HqAdmin);
        $engine = app(TransactionRepairEngine::class);
        $realIssue = $engine->scanBranch($branch)[0];
        $forgedIssue = new RepairIssue($realIssue->issueCode, $caseA->id, $caseA->branch_id, BankProcess::class, $target->id, Repairability::AutoFixable, 'forged', $realIssue->evidence);
        $plan = new RepairPlan($forgedIssue, 'GENERATE_SP3K_SYSTEM_CODE', ['sp3k_code' => null], ['sp3k_code' => 'SYSTEM GENERATED'], hash('sha256', 'forged'));

        try {
            $engine->apply($user, $plan);
            $this->fail('Cross-case repair unexpectedly applied.');
        } catch (ValidationException $exception) {
            $this->assertStringContainsString('does not belong', $exception->getMessage());
        }
        $this->assertNull($target->refresh()->sp3k_code);
        $this->assertSame(0, BusinessNumberSequence::query()->count());
        $this->assertSame(0, RepairAction::query()->count());

        $realPlan = $engine->plan($realIssue);
        $fakeAction = new RepairPlan($realPlan->issue, 'SOMETHING_FAKE', $realPlan->before, $realPlan->proposed, $realPlan->fingerprint);
        $this->expectException(ValidationException::class);
        $engine->apply($user, $fakeAction);
    }

    public function test_scan_command_is_branch_scoped_read_only_and_deterministic(): void
    {
        $branch = Branch::factory()->create();
        $case = SalesCase::factory()->create(['branch_id' => $branch->id]);
        DeveloperPpjb::factory()->create(['sales_case_id' => $case->id]);

        $this->assertSame(1, Artisan::call('oasis:repair-scan', ['--branch-id' => 'missing', '--json' => true]));
        $this->assertSame(0, Artisan::call('oasis:repair-scan', ['--branch-id' => $branch->id, '--json' => true]));
        $first = Artisan::output();
        $this->assertSame(0, Artisan::call('oasis:repair-scan', ['--branch-id' => $branch->id, '--json' => true]));
        $this->assertSame($first, Artisan::output());
        $this->assertNull($case->developerPpjbs()->firstOrFail()->ppjb_code);
        $this->assertSame(0, RepairAction::query()->count());
    }
}
