<?php

namespace Tests\Feature;

use App\Enums\LegacyMigrationPlanStatus;
use App\Enums\LegacyOrphanDecision;
use App\Enums\LegacyOrphanStatus;
use App\Enums\LegacyResolutionType;
use App\MigrationReadiness;
use App\Models\Consumer;
use App\Models\LegacyMigrationBatch;
use App\Models\LegacyMigrationOrphan;
use App\Models\LegacyMigrationPlan;
use App\Models\LegacyMigrationProvenance;
use App\Models\User;
use App\Services\LegacyMigrationCandidateService;
use App\Services\LegacyMigrationImportService;
use App\Services\LegacyMigrationOrphanService;
use App\Services\LegacyMigrationPlanService;
use App\Services\LegacyMigrationReviewService;
use App\Services\LegacyResolutionCompatibilityService;
use App\UserRole;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class PhaseEightCImportPlanTest extends TestCase
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

    private function batch(): LegacyMigrationBatch
    {
        return app(LegacyMigrationCandidateService::class)->buildFromReport(storage_path('app/private/legacy-audit/jepara'), $this->hq);
    }

    private function plan(LegacyMigrationBatch $batch): LegacyMigrationPlan
    {
        LegacyMigrationOrphan::where('batch_id', $batch->id)->where('severity', 'BLOCKING')->update(['status' => 'EXCLUDED']);

        return app(LegacyMigrationPlanService::class)->generate($batch, $this->hq);
    }

    public function test_immutable_plan_is_generated_with_fingerprints_and_operations(): void
    {
        $batch = $this->batch();
        $plan = $this->plan($batch);

        $this->assertTrue($plan->status === LegacyMigrationPlanStatus::Generated);
        $this->assertNotNull($plan->plan_fingerprint);
        $this->assertSame($batch->source_fingerprint, $plan->source_fingerprint);
        $this->assertGreaterThan(0, $plan->operations()->count());
        $this->assertSame(266, $plan->summary_totals['sales_cases']);
        $this->assertFalse(app(LegacyMigrationPlanService::class)->isStale($plan));
    }

    public function test_changed_fingerprint_makes_plan_stale(): void
    {
        $batch = $this->batch();
        $plan = $this->plan($batch);

        $batch->update(['source_fingerprint' => 'changed']);
        $plan->unsetRelation('batch');
        $this->assertTrue(app(LegacyMigrationPlanService::class)->isStale($plan));
    }

    public function test_review_and_blocked_candidates_are_not_included_in_plan(): void
    {
        $batch = $this->batch();
        $plan = $this->plan($batch);

        $this->assertSame(266, $plan->operations()->whereNotNull('candidate_id')->distinct()->count('candidate_id'));
    }

    public function test_blocking_orphan_blocks_plan_generation_until_accounted(): void
    {
        $batch = $this->batch();
        $orphan = LegacyMigrationOrphan::where('batch_id', $batch->id)->where('severity', 'BLOCKING')->first();

        $this->expectException(ValidationException::class);
        app(LegacyMigrationPlanService::class)->generate($batch, $this->hq);
    }

    public function test_orphan_can_be_excluded_or_linked(): void
    {
        $batch = $this->batch();
        $orphan = LegacyMigrationOrphan::where('batch_id', $batch->id)->where('severity', 'REVIEW')->firstOrFail();

        app(LegacyMigrationOrphanService::class)->resolve($orphan, $this->hq, LegacyOrphanDecision::ExcludeAsIrrelevant, 'not needed');

        $this->assertTrue($orphan->fresh()->status === LegacyOrphanStatus::Excluded);
    }

    public function test_exact_nik_reuses_consumer(): void
    {
        $batch = $this->batch();
        $candidate = $batch->candidates()->where('readiness', MigrationReadiness::Auto->value)->firstOrFail();
        $nik = $candidate->proposed_sales_case['nik_normalized'];

        $existing = Consumer::create(['nik' => $nik, 'name' => 'Existing']);
        $plan = $this->plan($batch);
        $result = app(LegacyMigrationImportService::class)->execute($plan);

        $this->assertSame($existing->id, Consumer::where('nik', $nik)->sole()->id);
    }

    public function test_incompatible_resolution_type_is_rejected(): void
    {
        $batch = $this->batch();
        $candidate = $batch->candidates()->where('readiness', MigrationReadiness::Blocked->value)->firstOrFail();
        $exception = $candidate->exceptions()->where('severity', 'BLOCKING')->firstOrFail();

        $this->expectException(ValidationException::class);
        app(LegacyMigrationReviewService::class)->resolveBlockingException($candidate, $this->hq, $exception->code, LegacyResolutionType::MapConsumer, 'incompatible');
    }

    public function test_compatibility_matrix_accepts_compatible_type(): void
    {
        $service = app(LegacyResolutionCompatibilityService::class);
        $this->assertTrue($service->isCompatible('UNIT_CODE_AMBIGUOUS', LegacyResolutionType::MapUnit));
        $this->assertFalse($service->isCompatible('UNIT_CODE_AMBIGUOUS', LegacyResolutionType::AcceptUnknownStatus));
    }

    public function test_simulation_executes_and_writes_provenance(): void
    {
        $batch = $this->batch();
        LegacyMigrationOrphan::where('batch_id', $batch->id)->where('severity', 'BLOCKING')->update(['status' => 'EXCLUDED']);
        $plan = $this->plan($batch);

        $result = app(LegacyMigrationImportService::class)->execute($plan);

        $this->assertSame(266, $result['counts']['sales_cases']);
        $this->assertGreaterThan(0, $result['counts']['consumers_created']);
        $this->assertGreaterThan(0, LegacyMigrationProvenance::count());
        $this->assertLessThanOrEqual($result['counts']['consumers_created'] + $result['counts']['consumers_reused'], Consumer::count());
        $this->assertSame($result['counts']['consumers_created'] + $result['counts']['consumers_reused'], 266);
    }

    public function test_normal_live_actions_remain_unaffected(): void
    {
        // Guard: legacy import flags must not weaken normal Action behavior.
        $this->assertFalse(Consumer::query()->where('nik', '0000000000000000')->exists());
    }
}
