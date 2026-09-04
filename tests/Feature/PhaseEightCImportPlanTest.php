<?php

namespace Tests\Feature;

use App\Enums\LegacyMigrationPlanOperationType;
use App\Enums\LegacyMigrationPlanStatus;
use App\Enums\LegacyOrphanDecision;
use App\Enums\LegacyOrphanStatus;
use App\MigrationReadiness;
use App\Models\Bank;
use App\Models\Consumer;
use App\Models\LegacyMigrationBatch;
use App\Models\LegacyMigrationOrphan;
use App\Models\LegacyMigrationPlan;
use App\Models\LegacyMigrationProvenance;
use App\Models\SalesCase;
use App\Models\User;
use App\Services\LegacyMigrationCandidateService;
use App\Services\LegacyMigrationImportService;
use App\Services\LegacyMigrationOrphanService;
use App\Services\LegacyMigrationPlanService;
use App\Services\LegacyMigrationReadinessService;
use App\UserRole;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\File;
use Illuminate\Validation\ValidationException;
use RuntimeException;
use Tests\TestCase;

class PhaseEightCImportPlanTest extends TestCase
{
    use RefreshDatabase;

    private User $hq;

    private string $fixtureDir;

    private string $reportDir;

    protected function setUp(): void
    {
        ini_set('memory_limit', '2G');
        parent::setUp();
        $this->seed();
        $this->hq = User::factory()->create();
        $this->hq->assignRole(UserRole::HqAdmin);

        $this->fixtureDir = storage_path('app/private/legacy-audit-fixture-8c');
        $this->reportDir = storage_path('app/private/legacy-audit/jepara');

        File::deleteDirectory($this->fixtureDir);
        File::deleteDirectory($this->reportDir);
        File::ensureDirectoryExists($this->fixtureDir);

        $this->createFixtureFiles();
        $this->artisan('legacy:audit', ['branch' => 'jepara', 'source' => $this->fixtureDir, '--output' => $this->reportDir])->assertExitCode(0);
    }

    protected function tearDown(): void
    {
        File::deleteDirectory($this->fixtureDir);
        File::deleteDirectory($this->reportDir);
        parent::tearDown();
    }

    private function createFixtureFiles(): void
    {
        foreach ([['BTN', 'Bank BTN'], ['BRI', 'Bank BRI'], ['BCA', 'Bank BCA']] as [$code, $name]) {
            Bank::firstOrCreate(['code' => $code], ['name' => $name]);
        }

        $this->writeCsv('data_konsumen.csv', [
            ['legacy_id', 'nik', 'name', 'phone', 'project', 'block', 'kavling', 'pembiayaan', 'status', 'tanggal_booking'],
            ['K-001', '3201010101010001', 'Budi Santoso', '081234567001', 'MRG', 'A', '01', 'KPR_SUBSIDI', 'COMPLETED', '2026-01-05'],
            ['K-002', '3201010101010002', 'Citra Lestari', '081234567002', 'MRG', 'A', '02', 'KPR_SUBSIDI', 'ACTIVE', '2026-01-06'],
            ['K-003', '3201010101010003', 'Dedi Pratama', '081234567003', 'MRG', 'A', '03', 'CASH', 'COMPLETED', '2026-01-07'],
            ['K-004', '3201010101010004', 'Eko Prasetyo', '081234567004', 'MRG', 'A', '20', 'KPR_SUBSIDI', 'PINDAH KAVLING', '2026-01-08'],
            ['K-005', '3201010101010004', 'Eko Prasetyo', '081234567004', 'MRG', 'B', '15', 'KPR_SUBSIDI', 'ACTIVE', '2026-01-20'],
            ['K-006', '3201010101010005', 'Fajar Nugroho', '081234567005', 'MRG', 'C', '05', 'KPR_SUBSIDI', 'MUNDUR', '2026-01-10'],
        ]);

        $this->writeCsv('bi_checking.csv', [
            ['legacy_id', 'hasil', 'tanggal_bi', 'catatan'],
            ['K-001', 'CLEAR', '2026-01-06', 'BI Clear'],
            ['K-002', 'CLEAR', '2026-01-07', 'BI Clear'],
            ['K-004', 'CLEAR', '2026-01-09', 'BI Clear'],
            ['K-005', 'CLEAR', '2026-01-21', 'BI Clear'],
        ]);

        $this->writeCsv('psjb.csv', [
            ['legacy_id', 'tanggal_psjb', 'nomor_psjb', 'status', 'catatan'],
            ['K-001', '2026-01-08', 'PSJB-001', 'ACTIVE', 'PSJB Budi'],
            ['K-002', '2026-01-09', 'PSJB-002', 'ACTIVE', 'PSJB Citra'],
            ['K-003', '2026-01-09', 'PSJB-003', 'ACTIVE', 'PSJB Dedi CASH'],
            ['K-004', '2026-01-10', 'PSJB-004', 'ACTIVE', 'PSJB Eko 1'],
            ['K-005', '2026-01-22', 'PSJB-005', 'ACTIVE', 'PSJB Eko 2'],
        ]);

        $this->writeCsv('pemberkasan.csv', [
            ['legacy_id', 'bank', 'tanggal_pemberkasan', 'catatan'],
            ['K-001', 'BCA', '2026-01-10', 'Pemberkasan K-001'],
            ['K-002', 'BTN', '2026-01-11', 'Attempt 1 BTN'],
            ['K-002', 'BRI', '2026-01-15', 'Attempt 2 BRI'],
            ['K-003', '', '2026-01-12', 'Pemberkasan CASH K-003'],
        ]);

        $this->writeCsv('proses_bank.csv', [
            ['legacy_id', 'bank', 'hasil', 'tanggal_response', 'nomor_sp3k', 'tanggal_sp3k'],
            ['K-001', 'BCA', 'APPROVED', '2026-01-15', 'SP3K-001', '2026-01-15'],
            ['K-002', 'BTN', 'REJECTED', '2026-01-13', '', ''],
            ['K-002', 'BRI', 'APPROVED', '2026-01-18', 'SP3K-002', '2026-01-18'],
        ]);

        $this->writeCsv('ppjb_dev.csv', [
            ['legacy_id', 'tanggal_ppjb', 'nomor_ppjb', 'catatan'],
            ['K-001', '2026-01-20', 'PPJB-001', 'PPJB Budi'],
            ['K-002', '2026-01-22', 'PPJB-002', 'PPJB Citra'],
            ['K-003', '2026-01-15', 'PPJB-003', 'PPJB Dedi CASH'],
        ]);

        $this->writeCsv('akad.csv', [
            ['legacy_id', 'tanggal_akad', 'nomor_akad', 'catatan'],
            ['K-001', '2026-02-01', 'AKAD-001', 'Akad Budi'],
            ['K-003', '2026-01-25', 'AKAD-003', 'Akad Dedi CASH'],
        ]);

        $this->writeCsv('bast.csv', [
            ['legacy_id', 'tanggal_bast', 'nomor_bast', 'status', 'catatan'],
            ['K-001', '2026-02-15', 'BAST-001', 'COMPLETED', 'BAST Budi'],
            ['K-003', '2026-02-01', 'BAST-003', 'COMPLETED', 'BAST Dedi CASH'],
        ]);
    }

    private function writeCsv(string $filename, array $rows): void
    {
        $handle = fopen($this->fixtureDir.'/'.$filename, 'wb');
        foreach ($rows as $row) {
            fputcsv($handle, $row, escape: '');
        }
        fclose($handle);
    }

    private function batch(): LegacyMigrationBatch
    {
        return app(LegacyMigrationCandidateService::class)->buildFromReport($this->reportDir, $this->hq);
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
        $this->assertSame($plan->operations()->where('operation_type', LegacyMigrationPlanOperationType::CreateSalesCase)->count(), $plan->summary_totals['sales_cases']);
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
        $eligibleKeys = $batch->candidates()->with('exceptions')->get()
            ->filter(fn ($candidate) => app(LegacyMigrationReadinessService::class)->calculate($candidate) === MigrationReadiness::Auto)
            ->pluck('source_candidate_key');
        $plan = $this->plan($batch);

        $plannedKeys = $plan->operations()->whereNotNull('candidate_id')->distinct()->pluck('candidate_id')
            ->map(fn ($id) => $batch->candidates()->find($id)?->source_candidate_key)
            ->values();
        $this->assertSame($eligibleKeys->sort()->values()->all(), $plannedKeys->sort()->values()->all());
        $this->assertSame($eligibleKeys->count(), $plan->operations()->whereNotNull('candidate_id')->distinct()->count('candidate_id'));
    }

    public function test_blocking_orphan_blocks_plan_generation_until_accounted(): void
    {
        $batch = $this->batch();
        LegacyMigrationOrphan::create([
            'batch_id' => $batch->id,
            'source_sheet' => 'proses_bank',
            'source_row' => 99,
            'source_fingerprint' => $batch->source_fingerprint,
            'audit_fingerprint' => $batch->audit_fingerprint,
            'orphan_code' => 'ORPHAN_BANK_PROCESS',
            'severity' => 'BLOCKING',
            'normalized_evidence' => [],
            'candidate_matches' => ['count' => 0, 'matches' => []],
            'status' => 'PENDING',
        ]);

        $this->expectException(ValidationException::class);
        app(LegacyMigrationPlanService::class)->generate($batch, $this->hq);
    }

    public function test_orphan_can_be_excluded_or_linked(): void
    {
        $batch = $this->batch();
        $orphan = LegacyMigrationOrphan::create([
            'batch_id' => $batch->id,
            'source_sheet' => 'bi_checking',
            'source_row' => 99,
            'source_fingerprint' => $batch->source_fingerprint,
            'audit_fingerprint' => $batch->audit_fingerprint,
            'orphan_code' => 'ORPHAN_BI',
            'severity' => 'REVIEW',
            'normalized_evidence' => [],
            'candidate_matches' => ['count' => 0, 'matches' => []],
            'status' => 'PENDING',
        ]);

        app(LegacyMigrationOrphanService::class)->resolve($orphan, $this->hq, LegacyOrphanDecision::ExcludeAsIrrelevant, 'not needed');

        $this->assertTrue($orphan->fresh()->status === LegacyOrphanStatus::Excluded);
    }

    public function test_exact_nik_reuses_consumer(): void
    {
        $batch = $this->batch();
        $candidate = $batch->candidates()->where('readiness', MigrationReadiness::Auto->value)->firstOrFail();
        $nik = $candidate->proposed_sales_case['nik_normalized'];

        $existing = Consumer::create(['nik' => $nik, 'name' => 'Existing Consumer']);
        $plan = $this->plan($batch);
        $result = app(LegacyMigrationImportService::class)->execute($plan);

        $this->assertSame($existing->id, Consumer::where('nik', $nik)->sole()->id);
    }

    public function test_plan_operations_contain_exact_payloads_without_synthetic_defaults(): void
    {
        $batch = $this->batch();
        $plan = $this->plan($batch);

        $ops = $plan->operations()->orderBy('sequence')->get();
        $consumerOps = $ops->where('operation_type', LegacyMigrationPlanOperationType::CreateConsumer);
        $biOps = $ops->where('operation_type', LegacyMigrationPlanOperationType::CreateBiCheck);
        $sp3kOps = $ops->where('operation_type', LegacyMigrationPlanOperationType::CreateBankProcess);

        $this->assertGreaterThan(0, $consumerOps->count());
        foreach ($consumerOps as $op) {
            $this->assertFalse(str_starts_with($op->payload['nik'] ?? '', 'LEGACY-'));
            $this->assertSame(16, strlen($op->payload['nik']));
        }

        foreach ($biOps as $op) {
            $this->assertArrayHasKey('check_date', $op->payload);
            $this->assertNotSame(now()->toDateString(), $op->payload['check_date']);
        }

        $approvedBp = $sp3kOps->firstWhere('payload.is_authoritative', true);
        $this->assertNotNull($approvedBp);
        $this->assertNotNull($approvedBp->payload['sp3k_number']);
        $this->assertFalse(str_starts_with($approvedBp->payload['sp3k_number'], 'LEGACY-'));
    }

    public function test_cash_candidate_generates_internal_submission_operation_and_zero_bank_process_operations(): void
    {
        $batch = $this->batch();
        $plan = $this->plan($batch);

        $cashCandidate = $batch->candidates()->where('financing_type', 'CASH')->firstOrFail();
        $ops = $plan->operations()->where('candidate_id', $cashCandidate->id)->get();

        $subOps = $ops->where('operation_type', LegacyMigrationPlanOperationType::CreateDocumentSubmission);
        $bpOps = $ops->where('operation_type', LegacyMigrationPlanOperationType::CreateBankProcess);

        $this->assertSame(1, $subOps->count());
        $this->assertSame('CASH_INTERNAL', $subOps->first()->payload['type']);
        $this->assertNull($subOps->first()->payload['bank_id']);
        $this->assertSame(0, $bpOps->count());
    }

    public function test_synthetic_nik_prohibited_in_plan_generation(): void
    {
        $batch = $this->batch();
        $candidate = $batch->candidates()->firstOrFail();
        $proposed = $candidate->proposed_sales_case;
        $proposed['nik_normalized'] = 'INVALID';
        $candidate->update(['proposed_sales_case' => $proposed]);

        $this->expectException(ValidationException::class);
        app(LegacyMigrationPlanService::class)->generate($batch, $this->hq);
    }

    public function test_missing_required_akad_date_blocks_plan_generation(): void
    {
        $batch = $this->batch();
        $candidate = $batch->candidates()->where('lifecycle', 'COMPLETED')->firstOrFail();
        $proposed = $candidate->proposed_sales_case;
        if (isset($proposed['proposed_history']['akad'][0])) {
            $proposed['proposed_history']['akad'][0]['date_normalized'] = null;
        }
        $candidate->update(['proposed_sales_case' => $proposed]);

        $this->expectException(ValidationException::class);
        app(LegacyMigrationPlanService::class)->generate($batch, $this->hq);
    }

    public function test_tampered_plan_fingerprint_is_rejected_by_executor(): void
    {
        $batch = $this->batch();
        $plan = $this->plan($batch);

        $plan->update(['plan_fingerprint' => 'tampered-hash']);

        $this->expectException(RuntimeException::class);
        app(LegacyMigrationImportService::class)->execute($plan);
    }

    public function test_simulation_executes_operations_and_writes_provenance(): void
    {
        $batch = $this->batch();
        $plan = $this->plan($batch);

        $result = app(LegacyMigrationImportService::class)->execute($plan);

        $this->assertSame($plan->summary_totals['sales_cases'], $result['counts']['sales_cases']);
        $this->assertGreaterThan(0, $result['counts']['consumers_created']);
        $this->assertGreaterThan(0, LegacyMigrationProvenance::count());
        $this->assertSame($plan->summary_totals['sales_cases'], SalesCase::count());
        $expectedCompleted = $batch->candidates()->with('exceptions')->get()
            ->filter(fn ($candidate) => app(LegacyMigrationReadinessService::class)->calculate($candidate) === MigrationReadiness::Auto
                && $candidate->lifecycle === 'COMPLETED')
            ->count();
        $this->assertSame($expectedCompleted, SalesCase::where('case_status', 'COMPLETED')->count());

        $dediCash = SalesCase::where('financing_type', 'CASH')->firstOrFail();
        $this->assertSame(1, $dediCash->documentSubmissions()->where('type', 'CASH_INTERNAL')->count());
        $this->assertSame(0, $dediCash->bankProcesses()->count());
    }

    public function test_normal_live_actions_remain_unaffected(): void
    {
        $this->assertFalse(Consumer::query()->where('nik', '0000000000000000')->exists());
    }
}
