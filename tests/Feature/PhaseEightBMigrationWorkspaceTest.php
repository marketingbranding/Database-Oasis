<?php

namespace Tests\Feature;

use App\Enums\LegacyMigrationPlanOperationType;
use App\Enums\LegacyResolutionType;
use App\Enums\MigrationExceptionSeverity;
use App\MigrationReadiness;
use App\MigrationReviewDecision;
use App\Models\Bank;
use App\Models\LegacyMigrationBatch;
use App\Models\LegacyMigrationCandidate;
use App\Models\LegacyMigrationCandidateException;
use App\Models\User;
use App\Services\LegacyMigrationCandidateService;
use App\Services\LegacyMigrationDryRunService;
use App\Services\LegacyMigrationPlanService;
use App\Services\LegacyMigrationReadinessService;
use App\Services\LegacyMigrationReviewService;
use App\Services\LegacyResolutionCompatibilityService;
use App\UserRole;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\File;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class PhaseEightBMigrationWorkspaceTest extends TestCase
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

        $this->fixtureDir = storage_path('app/private/legacy-audit-fixture-b8');
        $this->reportDir = storage_path('app/private/legacy-audit-b8/jepara');

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
            ['K-002', '3201010101010002', 'Dedi Pratama', '081234567002', 'MRG', 'A', '02', 'CASH', 'COMPLETED', '2026-01-06'],
            ['K-003', '3201010101010003', 'Citra Lestari', '081234567003', 'MRG', 'A', '03', 'KPR_SUBSIDI', 'ACTIVE', '2026-01-07'],
            ['K-004', '3201010101010004', 'Fajar Nugroho', '081234567004', 'MRG', 'A', '04', 'KPR_SUBSIDI', 'ACTIVE', '2026-01-08'],
        ]);

        $this->writeCsv('bi_checking.csv', [
            ['legacy_id', 'hasil', 'tanggal_bi', 'catatan'],
            ['K-001', 'CLEAR', '2026-01-06', ''],
            ['K-003', 'CLEAR', '', ''],
        ]);

        $this->writeCsv('psjb.csv', [
            ['legacy_id', 'tanggal_psjb', 'nomor_psjb', 'status', 'catatan'],
            ['K-001', '2026-01-08', 'PSJB-001', 'ACTIVE', ''],
            ['K-002', '2026-01-09', 'PSJB-002', 'ACTIVE', ''],
            ['K-003', '2026-01-09', 'PSJB-003', 'ACTIVE', ''],
            ['K-004', '2026-01-10', 'PSJB-004', 'ACTIVE', ''],
        ]);

        $this->writeCsv('pemberkasan.csv', [
            ['legacy_id', 'id_berkas', 'bank', 'tanggal_pemberkasan', 'catatan'],
            ['K-001', 'BERKAS-001', 'BCA', '2026-01-10', ''],
            ['K-002', 'BERKAS-002', '', '2026-01-10', ''],
            ['K-003', 'BERKAS-003', 'BRI', '2026-01-11', ''],
        ]);

        $this->writeCsv('proses_bank.csv', [
            ['legacy_id', 'id_berkas', 'bank', 'hasil', 'tanggal_response', 'nomor_sp3k', 'tanggal_sp3k'],
            ['K-001', 'BERKAS-001', 'BCA', 'APPROVED', '2026-01-15', 'SP3K-001', '2026-01-15'],
        ]);

        $this->writeCsv('ppjb_dev.csv', [
            ['legacy_id', 'tanggal_ppjb', 'nomor_ppjb', 'catatan'],
            ['K-001', '2026-01-20', 'PPJB-001', ''],
            ['K-002', '2026-01-20', 'PPJB-002', ''],
        ]);

        $this->writeCsv('akad.csv', [
            ['legacy_id', 'tanggal_akad', 'nomor_akad', 'catatan'],
            ['K-001', '2026-02-01', 'AKAD-001', ''],
            ['K-002', '2026-02-01', 'AKAD-002', ''],
        ]);

        $this->writeCsv('bast.csv', [
            ['legacy_id', 'tanggal_bast', 'nomor_bast', 'status', 'catatan'],
            ['K-001', '2026-02-10', 'BAST-001', 'COMPLETED', ''],
            ['K-002', '2026-02-10', 'BAST-002', 'COMPLETED', ''],
            ['K-004', '2026-02-12', 'BAST-004', 'COMPLETED', ''],
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

    public function test_build_batch_reproduces_deterministic_synthetic_distribution(): void
    {
        $batch = $this->batch();
        $report = json_decode((string) file_get_contents($this->reportDir.'/summary.json'), true);

        $totals = $batch->candidates()->get()->groupBy('readiness')->map->count();

        $this->assertCount(count($report['sales_cases']), $batch->candidates()->get());
        $this->assertGreaterThanOrEqual(1, $totals->get(MigrationReadiness::Auto->value, 0));
        $this->assertGreaterThanOrEqual(1, $totals->get(MigrationReadiness::Review->value, 0));
        $this->assertGreaterThanOrEqual(1, $totals->get(MigrationReadiness::Blocked->value, 0));
        $this->assertNotNull($batch->source_fingerprint);
        $this->assertNotNull($batch->audit_fingerprint);
        $this->assertFalse($batch->candidates()->whereHas('exceptions', fn ($query) => $query->whereIn('source_sheet', ['pivot_table_kc_jpr', 'pivot_table_pati', 'table_rekapan']))->exists());
    }

    public function test_blocked_candidate_cannot_be_accepted(): void
    {
        $batch = $this->batch();
        $candidate = $batch->candidates()->where('readiness', MigrationReadiness::Blocked->value)->firstOrFail();

        $this->expectException(ValidationException::class);
        app(LegacyMigrationReviewService::class)
            ->review($candidate, $this->hq, MigrationReviewDecision::Accept, 'bulk approve');
    }

    public function test_review_candidate_acceptance_requires_matching_fingerprint(): void
    {
        $batch = $this->batch();
        $candidate = $batch->candidates()->where('readiness', MigrationReadiness::Review->value)->firstOrFail();

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
        $batch = $this->batch();
        $candidate = $batch->candidates()->where('readiness', MigrationReadiness::Blocked->value)->firstOrFail();

        $exceptions = $candidate->exceptions()->where('severity', 'BLOCKING')->get();
        foreach ($exceptions as $exception) {
            app(LegacyMigrationReviewService::class)
                ->resolveBlockingException($candidate, $this->hq, $exception->code, app(LegacyResolutionCompatibilityService::class)->allowedFor($exception->code)[0], 'resolved');
        }

        $this->assertSame(MigrationReadiness::Review->value, app(LegacyMigrationReadinessService::class)->calculate($candidate)->value);
    }

    public function test_dry_run_plan_rejects_invariant_failures_and_totals_match_totals(): void
    {
        $batch = $this->batch();
        $plan = app(LegacyMigrationDryRunService::class)->plan($batch);

        $autoCount = $batch->candidates()->where('readiness', MigrationReadiness::Auto->value)->count();
        $this->assertSame($autoCount, $plan['totals']['sales_cases']);
        $this->assertSame(0, $plan['totals']['invariant_failures']);
        $this->assertSame($batch->candidates()->count(), count($plan['candidates']));

        $auto = $batch->candidates()->where('readiness', MigrationReadiness::Auto->value)->firstOrFail();
        $auto->update([
            'financing_type' => 'CASH',
            'proposed_history' => ['proses_bank' => [1]],
        ]);
        $tamperedPlan = app(LegacyMigrationDryRunService::class)->plan($batch);
        $this->assertGreaterThan(0, $tamperedPlan['totals']['invariant_failures']);
    }

    public function test_map_bank_resolution_unblocks_and_resolves_plan_bank(): void
    {
        $batch = $this->batch();

        $case = [
            'candidate_key' => 'probe-bank',
            'consumer_key' => 'nik:3201010101099999',
            'unit_key' => 'MRG|A-88',
            'nik_normalized' => '3201010101099999',
            'name_normalized' => 'Konsumen Probe Bank',
            'financing' => 'KPR_SUBSIDI',
            'lifecycle_status' => 'ACTIVE',
            'dates' => ['consumer' => '2026-01-05'],
            'process_rows' => ['data_konsumen' => [5], 'pemberkasan' => [5], 'proses_bank' => [5]],
            'proposed_history' => [
                'data_konsumen' => [['source_sheet' => 'data_konsumen', 'source_row' => 5, 'date_normalized' => '2026-01-05']],
                'bi_checking' => [],
                'psjb' => [['source_sheet' => 'psjb', 'source_row' => 5, 'psjb_number' => 'PSJB-P', 'date_normalized' => '2026-01-06', 'status' => 'ACTIVE']],
                'pemberkasan' => [['source_sheet' => 'pemberkasan', 'source_row' => 5, 'submission_number' => 'BERKAS-P', 'bank_name' => 'LEGACY-MEGA', 'date_normalized' => '2026-01-10', 'sequence' => 1]],
                'proses_bank' => [['source_sheet' => 'proses_bank', 'source_row' => 5, 'submission_number' => 'BERKAS-P', 'bank_name' => 'LEGACY-MEGA', 'response_normalized' => 'APPROVED', 'response_date_normalized' => '2026-01-15', 'sp3k_number' => 'SP3K-P', 'sp3k_date_normalized' => '2026-01-15', 'is_authoritative' => true]],
                'ppjb_dev' => [],
                'akad' => [],
                'bast' => [],
            ],
        ];

        $candidate = LegacyMigrationCandidate::create([
            'batch_id' => $batch->id,
            'source_candidate_key' => 'probe-bank',
            'proposed_consumer' => ['candidate_key' => 'nik:3201010101099999', 'name_original' => 'Konsumen Probe Bank'],
            'proposed_unit' => ['candidate_key' => 'MRG|A-88', 'project_original' => 'MRG', 'unit_original' => 'A-88'],
            'proposed_sales_case' => $case,
            'proposed_history' => $case['proposed_history'],
            'confidence' => 'HIGH',
            'readiness' => MigrationReadiness::Blocked,
            'lifecycle' => 'ACTIVE',
            'financing_type' => 'KPR_SUBSIDI',
            'source_evidence' => ['case_evidence' => [], 'financing_evidence' => []],
            'source_fingerprint' => $batch->source_fingerprint,
        ]);

        LegacyMigrationCandidateException::create([
            'candidate_id' => $candidate->id,
            'code' => 'BANK_NOT_FOUND',
            'severity' => MigrationExceptionSeverity::Blocking->value,
            'source_sheet' => 'pemberkasan',
            'source_row' => 5,
            'entity_type' => 'pemberkasan',
            'message' => 'Bank LEGACY-MEGA tidak ditemukan.',
            'evidence' => ['bank_name' => 'LEGACY-MEGA'],
        ]);

        $readiness = app(LegacyMigrationReadinessService::class);
        $this->assertSame(MigrationReadiness::Blocked->value, $readiness->calculate($candidate)->value);

        $mega = Bank::firstOrCreate(['code' => 'MEGA'], ['name' => 'Bank Mega']);
        app(LegacyMigrationReviewService::class)->resolveBlockingException(
            $candidate,
            $this->hq,
            'BANK_NOT_FOUND',
            LegacyResolutionType::MapBank,
            'map ke Bank Mega',
            ['bank_id' => $mega->id],
        );

        $this->assertSame(MigrationReadiness::Review->value, $readiness->calculate($candidate)->value);

        app(LegacyMigrationReviewService::class)->review($candidate, $this->hq, MigrationReviewDecision::Accept, 'setuju');
        $this->assertSame(MigrationReadiness::Auto->value, $readiness->calculate($candidate)->value);

        $plan = app(LegacyMigrationPlanService::class)->generate($batch, $this->hq);
        $bpOp = $plan->operations()->where('candidate_id', $candidate->id)
            ->where('operation_type', LegacyMigrationPlanOperationType::CreateBankProcess->value)
            ->firstOrFail();
        $this->assertSame($mega->id, $bpOp->payload['bank_id']);
        $this->assertSame('SP3K-P', $bpOp->payload['sp3k_number']);
        $this->assertSame('APPROVED', $bpOp->payload['response_type']);
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
