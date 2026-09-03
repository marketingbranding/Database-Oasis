<?php

namespace Tests\Feature;

use App\MigrationReadiness;
use App\MigrationReviewDecision;
use App\Models\LegacyMigrationBatch;
use App\Models\LegacyMigrationCandidate;
use App\Models\User;
use App\Services\LegacyMigrationCandidateService;
use App\Services\LegacyMigrationDryRunService;
use App\Services\LegacyMigrationReadinessService;
use App\Services\LegacyMigrationReviewService;
use App\UserRole;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class PhaseEightBMigrationWorkspaceTest extends TestCase
{
    use RefreshDatabase;

    private User $hq;

    protected function setUp(): void
    {
        ini_set('memory_limit', '2G');
        parent::setUp();
        $this->seed();
        $this->hq = User::factory()->create();
        $this->hq->assignRole(UserRole::HqAdmin);
    }

    public function test_build_batch_reproduces_approved_baseline_totals(): void
    {
        $batch = app(LegacyMigrationCandidateService::class)
            ->buildFromReport(storage_path('app/private/legacy-audit/jepara'), $this->hq);

        $totals = $batch->candidates()->get()->groupBy('readiness')->map->count();

        $this->assertSame(386, $batch->candidates()->count());
        $this->assertSame(266, $totals->get(MigrationReadiness::Auto->value, 0));
        $this->assertSame(60, $totals->get(MigrationReadiness::Review->value, 0));
        $this->assertSame(60, $totals->get(MigrationReadiness::Blocked->value, 0));
        $this->assertNotNull($batch->source_fingerprint);
        $this->assertNotNull($batch->audit_fingerprint);
        $this->assertFalse($batch->candidates()->whereHas('exceptions', fn ($query) => $query->whereIn('source_sheet', ['pivot_table_kc_jpr', 'pivot_table_pati', 'table_rekapan']))->exists());
    }

    public function test_blocked_candidate_cannot_be_accepted(): void
    {
        $batch = app(LegacyMigrationCandidateService::class)
            ->buildFromReport(storage_path('app/private/legacy-audit/jepara'), $this->hq);
        $candidate = $batch->candidates()->where('readiness', MigrationReadiness::Blocked->value)->first();

        $this->expectException(ValidationException::class);
        app(LegacyMigrationReviewService::class)
            ->review($candidate, $this->hq, MigrationReviewDecision::Accept, 'bulk approve');
    }

    public function test_review_candidate_acceptance_requires_matching_fingerprint(): void
    {
        $batch = app(LegacyMigrationCandidateService::class)
            ->buildFromReport(storage_path('app/private/legacy-audit/jepara'), $this->hq);
        $candidate = $batch->candidates()->where('readiness', MigrationReadiness::Review->value)->first();

        $readiness = app(LegacyMigrationReadinessService::class);
        $this->assertFalse($readiness->isMigrationReady($candidate));

        app(LegacyMigrationReviewService::class)
            ->review($candidate, $this->hq, MigrationReviewDecision::Accept, 'valid review');

        $this->assertTrue($readiness->isMigrationReady($candidate));

        $batch->update(['source_fingerprint' => 'tampered']);
        $candidate->unsetRelation('batch');
        $this->assertFalse($readiness->isMigrationReady($candidate));

        $this->expectException(ValidationException::class);
        app(LegacyMigrationReviewService::class)
            ->review($candidate, $this->hq, MigrationReviewDecision::Accept, 'invalid review');
    }

    public function test_resolving_all_blockers_moves_candidate_to_review_not_auto(): void
    {
        $batch = app(LegacyMigrationCandidateService::class)
            ->buildFromReport(storage_path('app/private/legacy-audit/jepara'), $this->hq);
        $candidate = $batch->candidates()->where('readiness', MigrationReadiness::Blocked->value)->first();

        $exceptions = $candidate->exceptions()->where('severity', 'BLOCKING')->get();
        foreach ($exceptions as $exception) {
            app(LegacyMigrationReviewService::class)
                ->resolveBlockingException($candidate, $this->hq, $exception->code, 'manual', 'resolved');
        }

        $this->assertSame(MigrationReadiness::Review->value, app(LegacyMigrationReadinessService::class)->calculate($candidate)->value);
    }

    public function test_dry_run_plan_rejects_invariant_failures_and_totals_match_totals(): void
    {
        $batch = app(LegacyMigrationCandidateService::class)
            ->buildFromReport(storage_path('app/private/legacy-audit/jepara'), $this->hq);
        $plan = app(LegacyMigrationDryRunService::class)->plan($batch);

        $this->assertSame(266, $plan['totals']['sales_cases']);
        $this->assertSame(0, $plan['totals']['invariant_failures']);
        $this->assertSame(386, count($plan['candidates']));

        $auto = $batch->candidates()->where('readiness', MigrationReadiness::Auto->value)->firstOrFail();
        $auto->update([
            'financing_type' => 'CASH',
            'proposed_history' => ['proses_bank' => [1]],
        ]);
        $tamperedPlan = app(LegacyMigrationDryRunService::class)->plan($batch);
        $this->assertGreaterThan(0, $tamperedPlan['totals']['invariant_failures']);
    }

    public function test_auditor_cannot_review_and_branch_admin_cannot_mutate(): void
    {
        $auditor = User::factory()->create();
        $auditor->assignRole(UserRole::Auditor);
        $branchAdmin = User::factory()->create();
        $branchAdmin->assignRole(UserRole::BranchAdmin);

        $this->assertTrue($auditor->can('viewAny', LegacyMigrationBatch::class));
        $this->assertFalse($auditor->can('create', LegacyMigrationBatch::class));
        $this->assertFalse($branchAdmin->can('viewAny', LegacyMigrationBatch::class));
        $this->assertFalse($branchAdmin->can('review', LegacyMigrationCandidate::class));
    }
}
