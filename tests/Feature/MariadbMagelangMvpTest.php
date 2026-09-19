<?php

namespace Tests\Feature;

use App\Actions\CreateSalesCaseAction;
use App\Actions\MoveSalesCaseUnitAction;
use App\DeveloperPpjbStatus;
use App\FinancingType;
use App\Models\Bank;
use App\Models\BankProcess;
use App\Models\Branch;
use App\Models\Consumer;
use App\Models\DeveloperPpjb;
use App\Models\Project;
use App\Models\Psjb;
use App\Models\SalesCase;
use App\Models\Unit;
use App\Models\User;
use App\PsjbStatus;
use App\SalesCaseStage;
use App\SalesCaseStatus;
use App\Services\MagelangImport\MagelangImporter;
use App\Support\Database\PartialUniqueGuard;
use App\UnitStatus;
use App\UserRole;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class MariadbMagelangMvpTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed();
    }

    private function hqAdmin(): User
    {
        $user = User::factory()->create();
        $user->assignRole(UserRole::HqAdmin);

        return $user;
    }

    private function makeUnit(Branch $branch, ?string $code = null): Unit
    {
        return Unit::factory()
            ->for(Project::factory()->for($branch))
            ->create($code !== null ? ['unit_code' => $code] : []);
    }

    private function createCase(User $user, Unit $unit, array $extra = []): SalesCase
    {
        return app(CreateSalesCaseAction::class)->handle($user, array_merge([
            'unit_id' => $unit->id,
            'financing_type' => FinancingType::KprSubsidi,
            'consumer_id' => Consumer::factory()->create()->id,
        ], $extra));
    }

    // ------------------------------------------------------------ 1. strategy

    public function test_mariadb_guard_strategy_is_represented_in_migrations(): void
    {
        $path = database_path('migrations/2026_09_02_131522_create_sales_cases_table.php');
        $content = (string) file_get_contents($path);

        $this->assertStringContainsString("ulid('active_unit_key')->nullable()", $content);
        $this->assertStringContainsString('createMysqlTriggerGuard', $content);
        $this->assertStringNotContainsString('storedAs', $content);
        $this->assertStringNotContainsString('virtualAs', $content);
        // The consumer ACTIVE guard must not be created anymore.
        $this->assertStringNotContainsString('sales_cases_consumer_active_unique ON sales_cases (consumer_id)', $content);
        // Legacy consumer indexes are still dropped on rollback.
        $this->assertStringContainsString('sales_cases_consumer_active_unique', $content);

        // The helper itself is driver-aware and safe to call on SQLite.
        $this->assertTrue(PartialUniqueGuard::supportsPartialIndexes());
        PartialUniqueGuard::dropIndex('mariadb_mvp_probe_index', 'sales_cases');
        $this->assertTrue(true);
    }

    // -------------------------------------------------- 2. multiple ACTIVE ok

    public function test_same_consumer_can_hold_two_active_sales_cases(): void
    {
        $user = $this->hqAdmin();
        $branch = Branch::factory()->create();
        $consumer = Consumer::factory()->create();

        $first = $this->createCase($user, $this->makeUnit($branch), ['consumer_id' => $consumer->id]);
        $second = $this->createCase($user, $this->makeUnit($branch), ['consumer_id' => $consumer->id]);

        $this->assertTrue($first->case_status === SalesCaseStatus::Active);
        $this->assertTrue($second->case_status === SalesCaseStatus::Active);
        $this->assertSame(2, $consumer->salesCases()->active()->count());
    }

    // ------------------------------------------------- 3. unit guard remains

    public function test_same_unit_cannot_hold_two_active_sales_cases(): void
    {
        $user = $this->hqAdmin();
        $unit = $this->makeUnit(Branch::factory()->create());
        $case = $this->createCase($user, $unit);

        $this->expectException(ValidationException::class);

        $this->createCase($user, $unit, ['consumer_id' => Consumer::factory()->create()->id]);
    }

    public function test_active_unit_guard_is_enforced_by_database_constraint(): void
    {
        $user = $this->hqAdmin();
        $case = $this->createCase($user, $this->makeUnit(Branch::factory()->create()));

        $this->expectException(UniqueConstraintViolationException::class);

        SalesCase::create([
            'consumer_id' => Consumer::factory()->create()->id,
            'unit_id' => $case->unit_id,
            'project_id' => $case->project_id,
            'branch_id' => $case->branch_id,
            'financing_type' => FinancingType::KprSubsidi,
            'current_stage' => SalesCaseStage::DataKonsumen,
            'case_status' => SalesCaseStatus::Active,
            'created_by' => $user->id,
        ]);
    }

    // ------------------------------------------- 4. history reuses the unit

    public function test_closed_case_frees_unit_for_reuse(): void
    {
        $user = $this->hqAdmin();
        $branch = Branch::factory()->create();
        $unit = $this->makeUnit($branch);

        $first = $this->createCase($user, $unit);
        $first->update(['case_status' => SalesCaseStatus::Mundur, 'closed_at' => now()]);
        $unit->update(['status' => UnitStatus::Tersedia]);

        $second = $this->createCase($user, $unit);

        $this->assertTrue($second->case_status === SalesCaseStatus::Active);
        $this->assertSame(2, $unit->salesCases()->count());
    }

    // --------------------------------- 5/7/8. single-active milestone guards

    public function test_only_one_active_psjb_per_case_with_history_allowed(): void
    {
        $case = $this->createCase($this->hqAdmin(), $this->makeUnit(Branch::factory()->create()));

        $first = Psjb::factory()->create(['sales_case_id' => $case->id]);
        $first->update(['status' => PsjbStatus::Superseded]);

        $second = Psjb::factory()->create(['sales_case_id' => $case->id]);

        $this->assertTrue($second->status === PsjbStatus::Active);
        $this->assertSame(2, $case->psjbs()->count());

        $this->expectException(UniqueConstraintViolationException::class);

        Psjb::factory()->create(['sales_case_id' => $case->id]);
    }

    public function test_only_one_active_developer_ppjb_per_case_with_history_allowed(): void
    {
        $case = $this->createCase($this->hqAdmin(), $this->makeUnit(Branch::factory()->create()));

        $first = DeveloperPpjb::factory()->create(['sales_case_id' => $case->id]);
        $first->update(['status' => DeveloperPpjbStatus::Superseded]);

        $second = DeveloperPpjb::factory()->create(['sales_case_id' => $case->id]);

        $this->assertTrue($second->status === DeveloperPpjbStatus::Active);

        $this->expectException(UniqueConstraintViolationException::class);

        DeveloperPpjb::factory()->create(['sales_case_id' => $case->id]);
    }

    // --------------------------------------- 6. authoritative bank process

    public function test_only_one_authoritative_bank_process_per_case(): void
    {
        $case = $this->createCase($this->hqAdmin(), $this->makeUnit(Branch::factory()->create()));

        BankProcess::factory()->create(['sales_case_id' => $case->id]);
        BankProcess::factory()->create(['sales_case_id' => $case->id]);

        $authoritative = BankProcess::factory()->approved()->create(['sales_case_id' => $case->id]);

        $this->assertTrue($authoritative->is_authoritative);
        $this->assertSame(3, $case->bankProcesses()->count());

        $this->expectException(UniqueConstraintViolationException::class);

        BankProcess::factory()->approved()->create(['sales_case_id' => $case->id]);
    }

    // ------------------------------------------------------- 9. transfer

    public function test_unit_transfer_releases_old_unit_and_keeps_case_active(): void
    {
        $user = $this->hqAdmin();
        $branch = Branch::factory()->create();
        $case = $this->createCase($user, $this->makeUnit($branch));
        $oldUnitId = $case->unit_id;
        $newUnit = $this->makeUnit($branch);

        $moved = app(MoveSalesCaseUnitAction::class)->handle($user, $case, $newUnit->id, 'Dekat jalan raya');

        $this->assertSame($case->id, $moved->id);
        $this->assertTrue($moved->case_status === SalesCaseStatus::Active);
        $this->assertNull($moved->closed_at);
        $this->assertSame($newUnit->id, $moved->unit_id);
        $this->assertSame(UnitStatus::Tersedia->value, Unit::find($oldUnitId)->status->value);
        $this->assertSame(UnitStatus::Booking->value, $newUnit->fresh()->status->value);
        $this->assertSame(1, $moved->caseNotes()->count());
    }

    // --------------------------------- 10. cases never merge across units

    public function test_consumer_with_multiple_transactions_keeps_separate_cases(): void
    {
        $user = $this->hqAdmin();
        $branch = Branch::factory()->create();
        $consumer = Consumer::factory()->create(['name' => 'Siti Rahayu']);

        $this->createCase($user, $this->makeUnit($branch), ['consumer_id' => $consumer->id]);
        $this->createCase($user, $this->makeUnit($branch), ['consumer_id' => $consumer->id]);

        // Both transactions belong to the same consumer record; the cases are
        // never merged into one. (The shared createCase helper instantiates
        // one unused consumer per call, so only relational counts apply here.)
        $cases = SalesCase::query()->whereBelongsTo($consumer)->get();

        $this->assertSame(2, $cases->count());
        $this->assertTrue($cases->every(fn (SalesCase $case): bool => $case->consumer_id === $consumer->id));
        $this->assertNotSame($cases[0]->id, $cases[1]->id);
        $this->assertSame('Siti Rahayu', $consumer->refresh()->name);
    }

    // -------------------------------------- legacy statuses stay readable

    public function test_legacy_statuses_remain_readable_but_are_not_current(): void
    {
        $this->assertFalse(SalesCaseStatus::PindahKavling->isCurrent());
        $this->assertFalse(SalesCaseStatus::Cancelled->isCurrent());
        $this->assertTrue(SalesCaseStatus::PindahKavling->isLegacy());
        $this->assertSame(
            [SalesCaseStatus::Active, SalesCaseStatus::Completed, SalesCaseStatus::Mundur, SalesCaseStatus::Reject],
            SalesCaseStatus::current(),
        );

        $case = SalesCase::factory()->create(['case_status' => SalesCaseStatus::PindahKavling]);

        $this->assertTrue($case->refresh()->case_status === SalesCaseStatus::PindahKavling);
    }

    // -------------------------------------------- 11/12. magelang import

    private function magelangBranch(): Branch
    {
        return Branch::factory()->create(['name' => 'Magelang']);
    }

    public function test_magelang_import_preserves_anomalous_rows_with_review_flag(): void
    {
        $branch = $this->magelangBranch();
        $this->makeUnit($branch, 'MGL-A1');
        $this->makeUnit($branch, 'MGL-A2');

        $report = app(MagelangImporter::class, ['branch' => $branch])->import([
            [
                'id_transaksi_v2' => 'MGL-001',
                'nik' => '',
                'name' => 'Tanpa NIK',
                'unit_code' => 'MGL-A1',
                'financing_type' => 'KPR',
                'status' => 'AKTIF',
                'psjb_date' => '2024-02-01',
            ],
            [
                'id_transaksi_v2' => 'MGL-002',
                'nik' => '123',
                'name' => 'NIK Rusak',
                'unit_code' => 'MGL-A2',
                'financing_type' => 'CASH',
                'status' => 'AKTIF',
            ],
        ]);

        $this->assertCount(2, $report['imported']);
        $this->assertCount(0, $report['failed']);

        $cases = SalesCase::query()->orderBy('id')->get();

        $this->assertCount(2, $cases);
        $this->assertTrue($cases[0]->needs_review);
        $this->assertStringContainsString('PERLU DICEK', (string) $cases[0]->needs_review_reason);
        $this->assertNull($cases[0]->consumer->nik);
        $this->assertTrue($cases[1]->needs_review);

        // No fake NIK was generated.
        $this->assertSame(0, Consumer::query()->where('nik', 'like', 'LEGACY-%')->count());
    }

    public function test_magelang_import_does_not_merge_cases_or_overwrite_profiles(): void
    {
        $branch = $this->magelangBranch();
        $this->makeUnit($branch, 'MGL-B1');
        $this->makeUnit($branch, 'MGL-B2');
        $existing = Consumer::factory()->create(['nik' => '3374010101900001', 'name' => 'Nama Asli']);

        $report = app(MagelangImporter::class, ['branch' => $branch])->import([
            ['id_transaksi_v2' => 'MGL-010', 'nik' => '3374010101900001', 'name' => 'Nama Berbeda', 'unit_code' => 'MGL-B1', 'financing_type' => 'KPR', 'status' => 'AKTIF'],
            ['id_transaksi_v2' => 'MGL-011', 'nik' => '3374010101900001', 'name' => 'Nama Asli', 'unit_code' => 'MGL-B2', 'financing_type' => 'KPR', 'status' => 'AKTIF'],
        ]);

        $this->assertCount(2, $report['imported']);
        $this->assertSame(1, Consumer::query()->where('nik', '3374010101900001')->count());
        $this->assertSame('Nama Asli', $existing->refresh()->name);
        $this->assertSame(2, SalesCase::query()->where('consumer_id', $existing->id)->count());

        $flagged = SalesCase::query()->needsReview()->count();
        $this->assertSame(1, $flagged);
    }

    public function test_magelang_import_creates_no_fake_milestone_dates(): void
    {
        $branch = $this->magelangBranch();
        $this->makeUnit($branch, 'MGL-C1');

        $report = app(MagelangImporter::class, ['branch' => $branch])->import([
            [
                'id_transaksi_v2' => 'MGL-020',
                'nik' => '3374010101900002',
                'name' => 'Akad Tanpa PSJB',
                'unit_code' => 'MGL-C1',
                'financing_type' => 'KPR',
                'status' => 'AKTIF',
                'akad_date' => '2024-06-10',
                'sp3k_number' => 'SP3K/001',
                'sp3k_date' => '2024-04-05',
            ],
        ]);

        $this->assertCount(1, $report['imported']);

        $case = SalesCase::query()->firstOrFail();

        // The real Akad is kept; no PSJB row is invented to fill the gap.
        $this->assertSame(0, $case->psjbs()->count());
        $this->assertSame('2024-06-10', $case->akad->akad_date->toDateString());
        $this->assertTrue($case->current_stage === SalesCaseStage::Akad);

        // SP3K stays modeled as the authoritative bank process, not a stage.
        $this->assertTrue($case->currentApprovedBankProcess instanceof BankProcess);
        $this->assertSame('SP3K/001', $case->currentApprovedBankProcess->sp3k_number);
        $this->assertNotContains('SP3K', array_map(fn (SalesCaseStage $stage): string => $stage->value, SalesCaseStage::cases()));
    }

    public function test_magelang_import_reports_unknown_unit_without_losing_other_rows(): void
    {
        $branch = $this->magelangBranch();
        $this->makeUnit($branch, 'MGL-D1');

        $report = app(MagelangImporter::class, ['branch' => $branch])->import([
            ['id_transaksi_v2' => 'MGL-030', 'nik' => '3374010101900003', 'name' => 'Unit Hilang', 'unit_code' => 'MGL-XX', 'financing_type' => 'KPR', 'status' => 'AKTIF'],
            ['id_transaksi_v2' => 'MGL-031', 'nik' => '3374010101900004', 'name' => 'Unit Ada', 'unit_code' => 'MGL-D1', 'financing_type' => 'KPR', 'status' => 'AKTIF'],
        ]);

        $this->assertCount(1, $report['imported']);
        $this->assertCount(1, $report['failed']);
        $this->assertSame('MGL-030', $report['failed'][0]['source_id']);
        $this->assertSame(1, SalesCase::query()->count());
    }

    public function test_magelang_import_full_kpr_chain_and_cash_bank_difference(): void
    {
        $branch = $this->magelangBranch();
        $this->makeUnit($branch, 'MGL-E1');
        $this->makeUnit($branch, 'MGL-E2');
        Bank::factory()->create(['name' => 'BTN']);

        $report = app(MagelangImporter::class, ['branch' => $branch])->import([
            [
                'id_transaksi_v2' => 'MGL-040',
                'nik' => '3374010101900005',
                'name' => 'KPR Lengkap',
                'unit_code' => 'MGL-E1',
                'financing_type' => 'KPR',
                'status' => 'SELESAI',
                'booking_date' => '2023-01-10',
                'psjb_date' => '2023-02-01',
                'pemberkasan_date' => '2023-03-01',
                'bank_name' => 'BTN',
                'sp3k_number' => 'SP3K/999',
                'sp3k_date' => '2023-05-01',
                'ppjb_date' => '2023-06-01',
                'akad_date' => '2023-07-01',
                'bast_date' => '2023-08-01',
            ],
            [
                'id_transaksi_v2' => 'MGL-041',
                'nik' => '3374010101900006',
                'name' => 'Tunai',
                'unit_code' => 'MGL-E2',
                'financing_type' => 'CASH',
                'status' => 'AKTIF',
                'pemberkasan_date' => '2024-01-05',
                'bank_name' => 'BTN',
                'sp3k_number' => 'SP3K/XXX',
            ],
        ]);

        $this->assertCount(2, $report['imported']);

        $kpr = SalesCase::query()->whereHas('consumer', fn ($query) => $query->where('name', 'KPR Lengkap'))->firstOrFail();

        $this->assertTrue($kpr->case_status === SalesCaseStatus::Completed);
        $this->assertTrue($kpr->current_stage === SalesCaseStage::Bast);
        $this->assertNotNull($kpr->akad);
        $this->assertNotNull($kpr->bast);
        $this->assertFalse($kpr->needs_review);

        $cash = SalesCase::query()->whereHas('consumer', fn ($query) => $query->where('name', 'Tunai'))->firstOrFail();

        $this->assertTrue($cash->financing_type === FinancingType::Cash);
        $this->assertSame(0, $cash->bankProcesses()->count());
        $this->assertTrue($cash->current_stage === SalesCaseStage::Pemberkasan);
    }

    public function test_magelang_import_full_rerun_skips_case_and_process_rows(): void
    {
        $branch = $this->magelangBranch();
        $this->makeUnit($branch, 'MGL-R1');
        Bank::factory()->create(['name' => 'BTN']);
        $row = [
            'id_transaksi_v2' => 'MGL-RERUN-1',
            'nik' => '3374010101900010',
            'name' => 'Rerun Lengkap',
            'unit_code' => 'MGL-R1',
            'financing_type' => 'KPR',
            'status' => 'SELESAI',
            'psjb_date' => '2023-02-01',
            'pemberkasan_date' => '2023-03-01',
            'bank_name' => 'BTN',
            'sp3k_number' => 'SP3K/RERUN',
            'sp3k_date' => '2023-05-01',
            'ppjb_date' => '2023-06-01',
            'akad_date' => '2023-07-01',
            'bast_date' => '2023-08-01',
        ];
        $importer = app(MagelangImporter::class, ['branch' => $branch]);

        $first = $importer->import([$row]);
        $second = $importer->import([$row]);
        $case = SalesCase::query()
            ->where('import_source', 'MARISON_V2_MGL')
            ->where('import_source_id', 'MGL-RERUN-1')
            ->firstOrFail();

        $this->assertCount(1, $first['imported']);
        $this->assertCount(0, $second['imported']);
        $this->assertSame([$case->id], $second['already_imported']);
        $this->assertSame('MARISON_V2_MGL', $case->import_source);
        $this->assertSame(1, SalesCase::query()->where('import_source', 'MARISON_V2_MGL')->where('import_source_id', 'MGL-RERUN-1')->count());
        $this->assertSame(1, $case->psjbs()->count());
        $this->assertSame(1, $case->documentSubmissions()->count());
        $this->assertSame(1, $case->bankProcesses()->count());
        $this->assertSame(1, $case->developerPpjbs()->count());
        $this->assertNotNull($case->akad);
        $this->assertNotNull($case->bast);
    }

    public function test_magelang_import_partial_rerun_imports_only_missing_transaction(): void
    {
        $branch = $this->magelangBranch();
        $this->makeUnit($branch, 'MGL-P1');
        $this->makeUnit($branch, 'MGL-P2');
        $firstRow = ['id_transaksi_v2' => 'MGL-PARTIAL-1', 'nik' => '3374010101900011', 'name' => 'Pertama', 'unit_code' => 'MGL-P1', 'financing_type' => 'KPR', 'status' => 'AKTIF', 'psjb_date' => '2024-01-01'];
        $secondRow = ['id_transaksi_v2' => 'MGL-PARTIAL-2', 'nik' => '3374010101900012', 'name' => 'Kedua', 'unit_code' => 'MGL-P2', 'financing_type' => 'KPR', 'status' => 'AKTIF', 'psjb_date' => '2024-01-02'];
        $importer = app(MagelangImporter::class, ['branch' => $branch]);
        $importer->import([$firstRow]);

        $report = $importer->import([$firstRow, $secondRow]);

        $this->assertCount(1, $report['already_imported']);
        $this->assertCount(1, $report['imported']);
        $this->assertSame(2, SalesCase::query()->count());
        $this->assertSame(2, Psjb::query()->count());
    }

    public function test_magelang_source_identity_is_transaction_not_nik(): void
    {
        $branch = $this->magelangBranch();
        $this->makeUnit($branch, 'MGL-I1');
        $this->makeUnit($branch, 'MGL-I2');
        $importer = app(MagelangImporter::class, ['branch' => $branch]);

        $report = $importer->import([
            ['id_transaksi_v2' => 'MGL-IDENTITY-1', 'nik' => '3374010101900013', 'name' => 'Sama', 'unit_code' => 'MGL-I1', 'financing_type' => 'KPR', 'status' => 'AKTIF'],
            ['id_transaksi_v2' => 'MGL-IDENTITY-2', 'nik' => '3374010101900013', 'name' => 'Sama', 'unit_code' => 'MGL-I2', 'financing_type' => 'KPR', 'status' => 'AKTIF'],
        ]);

        $this->assertCount(2, $report['imported']);
        $this->assertSame(2, SalesCase::query()->whereIn('import_source_id', ['MGL-IDENTITY-1', 'MGL-IDENTITY-2'])->count());
        $this->assertSame(1, Consumer::query()->where('nik', '3374010101900013')->count());
    }

    public function test_import_identity_has_composite_database_unique_constraint(): void
    {
        $case = SalesCase::factory()->create([
            'import_source' => 'MARISON_V2_MGL',
            'import_source_id' => 'MGL-UNIQUE',
        ]);

        $this->expectException(UniqueConstraintViolationException::class);

        SalesCase::factory()->create([
            'import_source' => 'MARISON_V2_MGL',
            'import_source_id' => 'MGL-UNIQUE',
            'unit_id' => Unit::factory()->for($case->project)->create()->id,
        ]);
    }

    public function test_same_import_source_id_can_exist_under_different_sources(): void
    {
        $first = SalesCase::factory()->create([
            'import_source' => 'MARISON_V2_MGL',
            'import_source_id' => 'SHARED-001',
        ]);

        $second = SalesCase::factory()->create([
            'import_source' => 'MARISON_V2_JPR',
            'import_source_id' => 'SHARED-001',
            'unit_id' => Unit::factory()->for($first->project)->create()->id,
        ]);

        $this->assertNotSame($first->id, $second->id);
        $this->assertSame(2, SalesCase::query()->where('import_source_id', 'SHARED-001')->count());
    }

    public function test_magelang_import_rejects_blank_source_identity_for_review(): void
    {
        $branch = $this->magelangBranch();
        $this->makeUnit($branch, 'MGL-BLANK');

        $report = app(MagelangImporter::class, ['branch' => $branch])->import([
            ['id_transaksi_v2' => '', 'nik' => '3374010101900014', 'name' => 'Tanpa ID', 'unit_code' => 'MGL-BLANK', 'financing_type' => 'KPR', 'status' => 'AKTIF'],
        ]);

        $this->assertCount(0, $report['imported']);
        $this->assertCount(1, $report['failed']);
        $this->assertSame(1, $report['anomalies']['missing_source_id']);
        $this->assertStringContainsString('PERLU DICEK', $report['failed'][0]['error']);
        $this->assertSame(0, SalesCase::query()->count());
    }

    public function test_magelang_import_never_uses_execution_time_as_closed_at(): void
    {
        $branch = $this->magelangBranch();
        $this->makeUnit($branch, 'MGL-CLOSE1');
        $this->makeUnit($branch, 'MGL-CLOSE2');
        $this->travelTo('2026-09-19 12:00:00');

        app(MagelangImporter::class, ['branch' => $branch])->import([
            ['id_transaksi_v2' => 'MGL-CLOSE-1', 'nik' => '3374010101900015', 'name' => 'Mundur', 'unit_code' => 'MGL-CLOSE1', 'financing_type' => 'KPR', 'status' => 'MUNDUR'],
            ['id_transaksi_v2' => 'MGL-CLOSE-2', 'nik' => '3374010101900016', 'name' => 'Selesai', 'unit_code' => 'MGL-CLOSE2', 'financing_type' => 'KPR', 'status' => 'SELESAI', 'akad_date' => '2024-07-01', 'bast_date' => '2024-08-01'],
        ]);

        $undated = SalesCase::query()->where('import_source_id', 'MGL-CLOSE-1')->firstOrFail();
        $completed = SalesCase::query()->where('import_source_id', 'MGL-CLOSE-2')->firstOrFail();
        $this->assertNull($undated->closed_at);
        $this->assertTrue($undated->needs_review);
        $this->assertStringContainsString('missing_closing_date', (string) $undated->needs_review_reason);
        $this->assertSame('2024-08-01', $completed->closed_at?->toDateString());
        $this->assertNotSame('2026-09-19', $completed->closed_at?->toDateString());
    }

    public function test_magelang_sp3k_without_dates_uses_legacy_missing_date_flag(): void
    {
        $branch = $this->magelangBranch();
        $this->makeUnit($branch, 'MGL-SP3K');
        Bank::factory()->create(['name' => 'BTN']);

        $report = app(MagelangImporter::class, ['branch' => $branch])->import([[
            'id_transaksi_v2' => 'MGL-SP3K-NO-DATE',
            'nik' => '3374010101900017',
            'name' => 'SP3K Tanpa Tanggal',
            'unit_code' => 'MGL-SP3K',
            'financing_type' => 'KPR',
            'status' => 'AKTIF',
            'bank_name' => 'BTN',
            'sp3k_number' => 'SP3K/REAL/001',
        ]]);
        $case = SalesCase::query()->where('import_source_id', 'MGL-SP3K-NO-DATE')->firstOrFail();
        $process = $case->bankProcesses()->firstOrFail();

        $this->assertCount(1, $report['imported']);
        $this->assertSame('SP3K/REAL/001', $process->sp3k_number);
        $this->assertNull($process->response_date);
        $this->assertNull($process->sp3k_date);
        $this->assertTrue($process->legacy_date_missing);
        $this->assertTrue($case->needs_review);
    }

    public function test_magelang_profile_counts_anomalies_without_writing(): void
    {
        $branch = $this->magelangBranch();
        $this->makeUnit($branch, 'MGL-F1');

        $summary = app(MagelangImporter::class, ['branch' => $branch])->profile([
            ['id_transaksi_v2' => 'MGL-050', 'nik' => '', 'name' => 'A', 'unit_code' => 'MGL-F1', 'financing_type' => 'KPR', 'status' => 'AKTIF'],
            ['id_transaksi_v2' => 'MGL-051', 'nik' => '', 'name' => 'B', 'unit_code' => 'MGL-NOPE', 'financing_type' => 'KPR', 'status' => 'BOGUS'],
        ]);

        $this->assertSame(2, $summary['rows']);
        $this->assertSame(2, $summary['sales_cases']);
        $this->assertSame(0, SalesCase::query()->count());
        $this->assertSame(0, Consumer::query()->count());
        $this->assertGreaterThanOrEqual(1, $summary['anomalies']['blank_nik'] ?? 0);
        $this->assertGreaterThanOrEqual(1, $summary['anomalies']['unknown_unit'] ?? 0);
    }

    public function test_sp3k_is_modeled_as_authoritative_bank_process(): void
    {
        $case = $this->createCase($this->hqAdmin(), $this->makeUnit(Branch::factory()->create()));

        BankProcess::factory()->approved()->create([
            'sales_case_id' => $case->id,
            'sp3k_number' => 'SP3K/777',
        ]);

        $approved = $case->currentApprovedBankProcess;

        $this->assertNotNull($approved);
        $this->assertSame('SP3K/777', $approved->sp3k_number);
        $this->assertSame('done', $case->refresh()->stageProgress()[SalesCaseStage::ProsesBank->value]);
    }
}
