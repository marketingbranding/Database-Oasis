<?php

namespace Tests\Feature;

use App\LegacyMigration\AuditExceptionCode;
use App\LegacyMigration\JeparaLegacyAuditor;
use App\LegacyMigration\LegacyNormalizer;
use App\LegacyMigration\LegacySourceReader;
use App\Models\BiCheck;
use App\Models\Branch;
use App\Models\Consumer;
use App\Models\Project;
use App\Models\SalesCase;
use App\Models\Unit;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use OpenSpout\Common\Entity\Row;
use OpenSpout\Writer\XLSX\Writer as XlsxWriter;
use Tests\TestCase;

class PhaseEightALegacyAuditTest extends TestCase
{
    use RefreshDatabase;

    private string $testRoot;

    private string $source;

    private string $output;

    protected function setUp(): void
    {
        parent::setUp();

        $this->testRoot = storage_path('framework/testing/legacy-audit');
        $this->source = $this->testRoot.'/input';
        $this->output = $this->testRoot.'/output';

        File::deleteDirectory($this->testRoot);
        File::ensureDirectoryExists($this->source);

        $this->write('data_konsumen.csv', [
            ['legacy_id', 'nik', 'name', 'phone', 'project', 'block', 'kavling', 'pembiayaan', 'status', 'tanggal_booking'],
            ['K-001', '3201010101010001', 'Konsumen Normal', '081234567001', 'MRG', 'A', '01', 'KPR_SUBSIDI', 'ACTIVE', '2026-01-05'],
            ['K-002', '3201010101010002', 'Konsumen Pindah', '081234567002', 'MRG', 'A', '20', 'KPR_SUBSIDI', 'PINDAH KAVLING', '2026-01-06'],
            ['K-003', '3201010101010002', 'Konsumen Pindah', '081234567002', 'MRG', 'B', '15', 'KPR_SUBSIDI', 'ACTIVE', '2026-01-20'],
            ['K-004', '3201010101010003', 'Konsumen Mundur', '081234567003', 'MRG', 'C', '05', 'KPR_SUBSIDI', 'MUNDUR', '2026-01-07'],
            ['K-005', '3201010101010004', 'Konsumen Baru Unit', '081234567004', 'MRG', 'C', '05', 'KPR_SUBSIDI', 'ACTIVE', '2026-02-01'],
            ['K-006', '3201010101010005', 'Konsumen Cash', '081234567005', 'MRG', 'A', '10', 'CASH', 'COMPLETED', '2026-01-08'],
            ['K-007', '', 'Duplikat Nama', '081234567006', 'MRG', 'D', '01', 'KPR_SUBSIDI', 'ACTIVE', '2026-01-09'],
            ['K-008', '', 'Duplikat Nama', '081234567007', 'MRG', 'D', '02', 'KPR_SUBSIDI', 'ACTIVE', '2026-01-10'],
            ['K-009', '320101010101009', 'Invalid Nik', '081234567009', 'MRG', 'D', '03', 'KPR_SUBSIDI', 'ACTIVE', '2026-13-45'],
            ['K-010', '', 'Tanpa Identitas', '081234567010', 'MRG', 'E', '01', 'KPR_SUBSIDI', 'ACTIVE', '2026-01-11'],
            ['K-011', '', 'Tanpa Identitas', '081234567011', 'MRG', 'E', '02', 'KPR_SUBSIDI', 'ACTIVE', '2026-01-12'],
            ['K-999', '3201010101019999', 'Konsumen Upstream', '081234569999', 'MRG', 'A', '01', 'KPR_SUBSIDI', 'ACTIVE', '2026-01-05'],
        ]);

        $this->write('bi_checking.csv', [
            ['legacy_id', 'hasil', 'tanggal_bi', 'catatan'],
            ['K-001', 'CLEAR', '2026-01-06', ''],
            ['K-001', 'REVIEW', '2026-01-07', 'perlu dokumen tambahan'],
            ['K-002', 'CLEAR', '2026-01-10', ''],
            ['K-003', 'CLEAR', '2026-01-21', ''],
            ['K-009', 'CLEAR', '2026-01-11', ''],
            ['K-777', 'CLEAR', '2026-01-11', 'orphan'],
        ]);

        $this->write('psjb.csv', [
            ['legacy_id', 'tanggal_psjb', 'nomor_psjb', 'status', 'catatan'],
            ['K-001', '2026-01-08', 'PSJB-001', 'ACTIVE', ''],
            ['K-002', '2026-01-11', 'PSJB-002', 'ACTIVE', ''],
            ['K-003', '2026-01-22', 'PSJB-002', 'ACTIVE', 'nomor sama lintas case'],
            ['K-004', '2026-01-12', 'PSJB-004', 'ACTIVE', ''],
            ['K-005', '2026-02-02', 'PSJB-005', 'ACTIVE', ''],
            ['K-006', '2026-01-09', 'PSJB-006', 'ACTIVE', ''],
            ['K-006', '2026-01-10', 'PSJB-006-R1', 'SUPERSEDED', 'terbit ulang'],
            ['K-777', '2026-01-11', 'PSJB-999', 'ACTIVE', 'orphan'],
        ]);

        $this->write('pemberkasan.csv', [
            ['legacy_id', 'bank', 'tanggal_pemberkasan', 'catatan'],
            ['K-001', 'BCA', '2026-01-12', ''],
            ['K-002', 'BTN', '2026-01-12', ''],
            ['K-002', 'BRI', '2026-01-15', ''],
            ['K-005', 'BRI', '2026-02-03', ''],
            ['K-777', 'BCA', '2026-01-13', 'orphan'],
        ]);

        $this->write('proses_bank.csv', [
            ['legacy_id', 'bank', 'hasil', 'tanggal_response', 'nomor_sp3k', 'tanggal_sp3k'],
            ['K-001', 'BCA', 'APPROVED', '2026-01-18', 'SP3K-UAT-001', '2026-01-18'],
            ['K-002', 'BTN', 'REJECTED', '2026-01-13', '', ''],
            ['K-002', 'BRI', 'PROCESS', '2026-01-16', '', ''],
            ['K-002', 'BRI', 'APPROVED', '2026-01-20', 'SP3K-DUP-1', '2026-01-20'],
            ['K-005', 'BRI', 'APPROVED', '2026-02-05', 'SP3K-DUP-1', '2026-02-05'],
            ['K-006', '', 'APPROVED', '2026-01-15', 'CASH', '2026-01-15'],
            ['K-009', 'BCA', 'APPROVED', '2026-01-14', 'SP3K-009', ''],
            ['K-777', 'BCA', 'APPROVED', '2026-01-14', 'SP3K-999', ''],
        ]);

        $this->write('ppjb_dev.csv', [
            ['legacy_id', 'tanggal_ppjb', 'nomor_ppjb', 'catatan'],
            ['K-001', '2026-01-22', 'PPJB-001', ''],
            ['K-002', '2026-01-25', 'PPJB-001', 'nomor sama lintas case'],
            ['K-005', '2026-02-08', 'PPJB-005', ''],
            ['K-006', '2026-01-18', 'PPJB-006', ''],
            ['K-999', '2026-01-30', 'PPJB-999', 'orphan'],
        ]);

        $this->write('akad.csv', [
            ['legacy_id', 'tanggal_akad', 'nomor_akad', 'catatan'],
            ['K-001', '2026-02-10', 'AKAD-001', ''],
            ['K-005', '2026-02-28', 'AKAD-005', 'akad tanpa PPJB pada case ini'],
            ['K-006', '2026-01-25', 'AKAD-006', ''],
            ['K-777', '2026-02-12', 'AKAD-999', 'orphan'],
        ]);

        $this->write('bast.csv', [
            ['legacy_id', 'tanggal_bast', 'nomor_bast', 'status', 'catatan'],
            ['K-001', '2026-01-04', 'BAST-001', 'COMPLETED', 'sebelum akad: pelanggaran kronologi'],
            ['K-005', '2026-03-01', 'BAST-005', 'COMPLETED', 'bast tanpa akad pada case ini'],
            ['K-006', '2026-02-15', 'BAST-006', 'COMPLETED', ''],
            ['K-999', '2026-03-05', 'BAST-999', 'COMPLETED', 'orphan tanpa akad'],
        ]);

        $this->write('ringkasan_data.csv', [
            ['rekap', 'jumlah'],
            ['akad', 2],
        ]);
    }

    protected function tearDown(): void
    {
        // Delete only the exact directory this test created. The real audit
        // path storage/app/private/legacy-audit is intentionally untouched.
        File::deleteDirectory($this->testRoot);

        parent::tearDown();
    }

    public function test_audit_reconstructs_jepara_fixtures_without_writing_domain_tables(): void
    {
        $branch = Branch::factory()->create();
        $project = Project::factory()->for($branch)->create();
        $unit = Unit::factory()->for($project)->create();
        $consumer = Consumer::factory()->create();
        SalesCase::factory()->for($unit)->for($consumer)->create();
        BiCheck::factory()->for(SalesCase::query()->sole())->create();

        $before = $this->tableCounts();
        $report = $this->app->make(JeparaLegacyAuditor::class)->audit($this->source);
        $this->dumpReport($report);
        $after = $this->tableCounts();

        $this->assertSame($before, $after, 'Audit harus read-only terhadap tabel domain.');

        $summary = $report['summary'];
        $cases = collect($report['sales_cases']);
        $consumers = collect($report['consumers']);

        $this->assertSame(12, $summary['legacy_rows_by_sheet']['data_konsumen']);
        $this->assertSame(12, $summary['proposed_sales_cases']);
        $this->assertSame(11, $summary['proposed_consumers']);
        $this->assertSame(11, $summary['kpr_cases']);
        $this->assertSame(1, $summary['cash_cases']);
        $this->assertSame(1, $summary['mundur_cases']);
        $this->assertSame(1, $summary['pindah_kavling_candidates']);

        $k002 = $cases->firstWhere('legacy_consumer_id', 'K-002');
        $k003 = $cases->firstWhere('legacy_consumer_id', 'K-003');
        $this->assertSame($k002['consumer_key'], $k003['consumer_key']);
        $this->assertSame('MRG|A-20', $k002['unit_key']);
        $this->assertSame('MRG|B-15', $k003['unit_key']);
        $this->assertSame($k002['candidate_key'], $k003['previous_case_candidate']);
        $this->assertCount(2, $consumers->firstWhere('candidate_key', $k002['consumer_key'])['legacy_rows']);
    }

    public function test_multi_bank_reject_then_approve_stays_one_sales_case(): void
    {
        $report = $this->app->make(JeparaLegacyAuditor::class)->audit($this->source);

        $k002 = collect($report['sales_cases'])->firstWhere('legacy_consumer_id', 'K-002');
        $processRows = $k002['process_rows']['proses_bank'];

        $this->assertSame([3, 4, 5], $processRows);
        $this->assertSame('PINDAH_KAVLING', $k002['lifecycle_status']);
        $multiBank = collect($report['duplicate_analysis'])->firstWhere('classification', 'MULTIPLE_BANK_ATTEMPT');
        $this->assertNotNull($multiBank);
        $this->assertContains('BTN', $multiBank['banks']);
        $this->assertContains('BRI', $multiBank['banks']);
    }

    public function test_duplicate_document_numbers_do_not_merge_cases(): void
    {
        $report = $this->app->make(JeparaLegacyAuditor::class)->audit($this->source);

        $duplicates = collect($report['duplicate_analysis'])
            ->where('classification', 'SAME_DOCUMENT_NUMBER_DIFFERENT_CASE');

        $this->assertCount(3, $duplicates);
        $this->assertSame(
            ['PPJB_NUMBER', 'PSJB_NUMBER', 'SP3K_NUMBER'],
            $duplicates->pluck('document_type')->sort()->values()->all(),
        );
        foreach ($duplicates as $duplicate) {
            $this->assertCount(2, $duplicate['sales_case_candidates']);
        }

        $exceptionCodes = collect($report['exceptions'])->pluck('code');
        $this->assertContains(AuditExceptionCode::DuplicateDocumentNumber->value, $exceptionCodes);
    }

    public function test_literal_cash_sp3k_is_placeholder_and_cash_flow_has_no_bank_process(): void
    {
        $report = $this->app->make(JeparaLegacyAuditor::class)->audit($this->source);

        $exceptionCodes = collect($report['exceptions'])->pluck('code');
        $this->assertContains(AuditExceptionCode::CashFakeSp3k->value, $exceptionCodes);

        $k006 = collect($report['sales_cases'])->firstWhere('legacy_consumer_id', 'K-006');
        $this->assertSame('CASH', $k006['financing']);
        $this->assertArrayHasKey('akad', $k006['process_rows']);
        $this->assertArrayHasKey('bast', $k006['process_rows']);
        $this->assertContains('CASH_FAKE_SP3K', collect($k006['process_rows']['proses_bank'] ?? [])->isEmpty() ? [] : $exceptionCodes);
    }

    public function test_identity_and_orphan_exceptions_are_classified(): void
    {
        $report = $this->app->make(JeparaLegacyAuditor::class)->audit($this->source);
        $codes = collect($report['exceptions'])->pluck('code')->unique()->values();

        $this->assertContains(AuditExceptionCode::ConsumerNikMissing->value, $codes);
        $this->assertContains(AuditExceptionCode::ConsumerIdentityAmbiguous->value, $codes);
        $this->assertContains(AuditExceptionCode::ConsumerNikInvalid->value, $codes);
        $this->assertContains(AuditExceptionCode::OrphanBi->value, $codes);
        $this->assertContains(AuditExceptionCode::OrphanPsjb->value, $codes);
        $this->assertContains(AuditExceptionCode::OrphanSubmission->value, $codes);
        $this->assertContains(AuditExceptionCode::PpjbWithoutUpstream->value, $codes);
        $this->assertContains(AuditExceptionCode::BastWithoutAkad->value, $codes);

        $ambiguous = collect($report['sales_cases'])->where('confidence', 'AMBIGUOUS');
        $this->assertSame(4, $ambiguous->count());
        $this->assertSame(
            ['duplikat nama', 'duplikat nama', 'tanpa identitas', 'tanpa identitas'],
            $ambiguous->pluck('name_normalized')->sort()->values()->all(),
        );
        $this->assertSame(4, collect($ambiguous->pluck('consumer_key'))->unique()->count());
    }

    public function test_invalid_date_and_chronology_violations_are_reported(): void
    {
        // Invalid date belongs to a process entity; data_konsumen is identity
        // reconstruction only and must not emit date exceptions.
        file_put_contents($this->source.'/pemberkasan.csv', implode("\n", [
            'legacy_id,bank,tanggal_pemberkasan,catatan',
            'K-001,BCA,2026-13-99,invalid tanggal proses',
        ]));

        $report = $this->app->make(JeparaLegacyAuditor::class)->audit($this->source);
        $codes = collect($report['exceptions'])->pluck('code');

        $this->assertContains(AuditExceptionCode::InvalidDate->value, $codes);

        $chronology = collect($report['chronology_issues']);
        $this->assertGreaterThanOrEqual(1, $chronology->count());
        $this->assertSame('bast', $chronology->first()['stage']);
        $this->assertSame('2026-01-04', $chronology->first()['date']);
    }

    public function test_exact_row_duplicates_are_flagged(): void
    {
        file_put_contents($this->source.'/data_konsumen.csv', file_get_contents($this->source.'/data_konsumen.csv'));

        $rows = file($this->source.'/data_konsumen.csv');
        $rows[] = $rows[1];
        file_put_contents($this->source.'/data_konsumen.csv', implode('', $rows));

        $report = $this->app->make(JeparaLegacyAuditor::class)->audit($this->source);
        $codes = collect($report['exceptions'])->pluck('code');

        $this->assertContains(AuditExceptionCode::ExactRowDuplicate->value, $codes);
        $duplicate = collect($report['duplicate_analysis'])->firstWhere('classification', 'EXACT_ROW_DUPLICATE');
        $this->assertNotNull($duplicate);
    }

    public function test_missing_nik_does_not_auto_merge_by_name(): void
    {
        $report = $this->app->make(JeparaLegacyAuditor::class)->audit($this->source);

        $d001 = collect($report['sales_cases'])->where('name_normalized', 'duplikat nama')->values();
        $this->assertCount(2, $d001);
        $this->assertNotSame($d001[0]['consumer_key'], $d001[1]['consumer_key']);
        $this->assertSame('AMBIGUOUS', $d001[0]['confidence']);
        $this->assertSame('AMBIGUOUS', $d001[1]['confidence']);
    }

    public function test_reconciliation_compares_legacy_and_reconstructed(): void
    {
        $report = $this->app->make(JeparaLegacyAuditor::class)->audit($this->source);

        $reconciliation = $report['reconciliation'];
        $this->assertSame(4, $reconciliation['legacy_secondary_baseline']['akad']);
        $this->assertSame(3, $reconciliation['reconstructed_candidates']['akad']);
        $this->assertSame(5, $reconciliation['legacy_secondary_baseline']['sp3k']);
        $this->assertSame(5, $reconciliation['reconstructed_candidates']['sp3k']);
        $this->assertArrayHasKey('differences', $reconciliation);
        $this->assertSame(['akad' => -1, 'bast' => 0, 'sp3k' => 0, 'active_transactions' => 0], $reconciliation['differences']);
    }

    public function test_reader_supports_xlsx_with_identical_report(): void
    {
        $xlsxPath = $this->testRoot.'/fixture-jepara.xlsx';
        $this->buildXlsx($xlsxPath);

        $csvReport = $this->app->make(JeparaLegacyAuditor::class)->audit($this->source);
        $xlsxReport = $this->app->make(JeparaLegacyAuditor::class)->audit($xlsxPath);

        $this->assertSame(
            collect($csvReport['summary'])->except('legacy_rows_by_sheet')->all(),
            collect($xlsxReport['summary'])->except('legacy_rows_by_sheet')->all(),
        );
        $this->assertSame(
            collect($csvReport['summary']['legacy_rows_by_sheet'])->except('ringkasan_data')->sortKeys()->all(),
            collect($xlsxReport['summary']['legacy_rows_by_sheet'])->except('ringkasan_data')->sortKeys()->all(),
        );
        $this->assertSame(
            collect($csvReport['sales_cases'])->pluck('candidate_key')->sort()->values()->all(),
            collect($xlsxReport['sales_cases'])->pluck('candidate_key')->sort()->values()->all(),
        );

        unlink($xlsxPath);
    }

    public function test_reader_marks_formulas_and_preserves_original_headers(): void
    {
        file_put_contents($this->source.'/akad.csv', implode("\n", [
            'legacy_id,tanggal_akad,nomor_akad,catatan',
            'K-001,2026-02-10,AKAD-001,=SUM(A1:A2)',
        ]));

        $sheets = $this->app->make(LegacySourceReader::class)->read($this->source);
        $akad = $sheets['akad'];
        $row = $akad['rows'][0];

        $this->assertSame('2026-02-10', $row['values']['akad_date']);
        $this->assertSame('AKAD-001', $row['values']['akad_number']);
        $this->assertSame('=SUM(A1:A2)', $row['original']['catatan']);
        $this->assertSame(['notes'], $row['formulas']);
        $this->assertContains('nomor_akad', $akad['original_headers']);
        $this->assertContains('tanggal_akad', $akad['original_headers']);
    }

    public function test_command_writes_protected_report_and_returns_success(): void
    {
        $this->artisan('legacy:audit', [
            'branch' => 'jepara',
            'source' => $this->source,
            '--output' => $this->output,
        ])->assertExitCode(0);

        $report = json_decode((string) file_get_contents($this->output.'/summary.json'), true);
        $this->assertSame('AUDIT_ONLY', $report['meta']['mode']);
        $this->assertFalse($report['meta']['normal_tables_written']);

        foreach (['consumers.csv', 'units.csv', 'sales_cases.csv', 'document_mapping.csv', 'exceptions.csv', 'duplicate_analysis.csv', 'chronology_issues.csv', 'unresolved_records.csv', 'schema_inventory.csv'] as $file) {
            $this->assertFileExists($this->output.'/'.$file);
        }
    }

    public function test_phase_eight_a_cleanup_cannot_delete_or_modify_external_sentinel(): void
    {
        $sentinel = storage_path('framework/testing/legacy-audit-sentinel.txt');
        $contents = 'real-migration-artifact-sentinel';
        file_put_contents($sentinel, $contents);

        try {
            $this->artisan('legacy:audit', [
                'branch' => 'jepara',
                'source' => $this->source,
                '--output' => $this->output,
            ])->assertExitCode(0);

            // Exercise the same exact cleanup scope as tearDown.
            File::deleteDirectory($this->testRoot);

            $this->assertFileExists($sentinel);
            $this->assertSame($contents, file_get_contents($sentinel));
        } finally {
            @unlink($sentinel);
        }
    }

    public function test_financing_contract_requires_header_and_never_defaults_blank_or_unknown_to_kpr(): void
    {
        $rows = array_map(fn (string $line): array => str_getcsv($line, escape: ''), file($this->source.'/data_konsumen.csv', FILE_IGNORE_NEW_LINES));
        $rows[0][] = 'status_cash';
        foreach ($rows as $index => &$row) {
            if ($index === 0) {
                continue;
            }
            $row[] = match ($row[0]) {
                'K-001' => '',
                'K-002' => 'MUNGKIN',
                'K-003' => 'TIDAK',
                'K-006' => 'YA',
                default => 'TIDAK',
            };
            // Force real-workbook financing contract rather than fixture's
            // canonical financing column.
            $row[7] = '';
        }
        unset($row);
        $this->write('data_konsumen.csv', $rows);

        $report = $this->app->make(JeparaLegacyAuditor::class)->audit($this->source);
        $cases = collect($report['sales_cases'])->keyBy('legacy_consumer_id');
        $codes = collect($report['exceptions'])->pluck('code');

        $this->assertContains(AuditExceptionCode::MissingFinancingStatus->value, $codes);
        $this->assertContains(AuditExceptionCode::FinancingUnresolved->value, $codes);
        $this->assertSame('KPR_SUBSIDI', $cases['K-001']['financing']);
        $this->assertSame('HIGH', $cases['K-001']['financing_confidence']);
        $this->assertContains('INFERRED_KPR_FROM_LINKED_SUBMISSION_AND_BANK_CHAIN', $cases['K-001']['financing_evidence']);
        $this->assertSame('KPR_SUBSIDI', $cases['K-003']['financing']);
        $this->assertSame('EXACT', $cases['K-003']['financing_confidence']);
        $this->assertSame('CASH', $cases['K-006']['financing']);
        $this->assertSame('EXACT', $cases['K-006']['financing_confidence']);
    }

    public function test_required_column_contract_reports_missing_status_cash_header(): void
    {
        $report = $this->app->make(JeparaLegacyAuditor::class)->audit($this->source);

        $missing = collect($report['exceptions'])
            ->where('code', AuditExceptionCode::MissingRequiredColumn->value)
            ->first(fn (array $exception): bool => str_contains($exception['message'], 'status_cash'));

        $this->assertNotNull($missing);
    }

    public function test_real_data_normalization_preserves_ambiguous_units_and_unknown_evidence(): void
    {
        $normalizer = $this->app->make(LegacyNormalizer::class);

        $this->assertSame('MARISON-PATI|A01', $normalizer->unitFromIdKavling('Marison Pati-A01'));
        $this->assertTrue($normalizer->hasDeterministicUnitSuffix('Marison Pati-A01'));
        $this->assertSame('RAW|PROJECT-TANPA-KODE', $normalizer->unitFromIdKavling('Project Tanpa-Kode'));
        $this->assertFalse($normalizer->hasDeterministicUnitSuffix('Project Tanpa-Kode'));
        $this->assertSame('CLEAR', $normalizer->statusValue('KOL 1'));
        $this->assertSame('REVIEW', $normalizer->statusValue('KOL 2'));
        $this->assertSame('KOL 3', $normalizer->statusValue('KOL 3'));
    }

    public function test_blank_process_statuses_and_bast_lifecycle_conflicts_are_explicit(): void
    {
        file_put_contents($this->source.'/bi_checking.csv', implode("\n", [
            'legacy_id,hasil,tanggal_bi,catatan',
            'K-001,,2026-01-06,hasil kosong',
        ]));
        file_put_contents($this->source.'/proses_bank.csv', implode("\n", [
            'legacy_id,bank,hasil,tanggal_response,nomor_sp3k,tanggal_sp3k',
            'K-001,BCA,,2026-01-18,,',
        ]));
        file_put_contents($this->source.'/bast.csv', implode("\n", [
            'legacy_id,tanggal_bast,nomor_bast,status,catatan',
            'K-002,2026-02-15,BAST-CONFLICT,COMPLETED,bertentangan dengan pindah kavling',
        ]));

        $report = $this->app->make(JeparaLegacyAuditor::class)->audit($this->source);
        $codes = collect($report['exceptions'])->pluck('code');

        $this->assertSame(2, $codes->filter(fn (string $code): bool => $code === AuditExceptionCode::MissingProcessStatus->value)->count());
        $this->assertContains(AuditExceptionCode::LifecycleConflict->value, $codes);
        $k002 = collect($report['sales_cases'])->firstWhere('legacy_consumer_id', 'K-002');
        $this->assertSame('PINDAH_KAVLING', $k002['lifecycle_status']);
    }

    public function test_command_rejects_non_jepara_branch(): void
    {
        $this->artisan('legacy:audit', ['branch' => 'semarang', 'source' => $this->source])
            ->assertExitCode(1);
    }

    private function write(string $filename, array $rows): void
    {
        $handle = fopen($this->source.'/'.$filename, 'wb');
        if ($handle === false) {
            self::fail("Fixture tidak dapat dibuat: {$filename}");
        }
        foreach ($rows as $row) {
            fputcsv($handle, $row, escape: '');
        }
        fclose($handle);
    }

    /** Dump actual report when test failures need investigation. */
    private function dumpReport(array $report, string $suffix = ''): void
    {
        File::ensureDirectoryExists($this->testRoot.'/debug');
        file_put_contents(
            $this->testRoot.'/debug/report'.$suffix.'.json',
            json_encode($report, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR),
        );
    }

    /** @return array<string, int> */
    private function tableCounts(): array
    {
        $tables = ['branches', 'projects', 'units', 'consumers', 'sales_cases', 'bi_checks', 'psjbs', 'document_submissions', 'bank_processes', 'developer_ppjbs', 'akad_records', 'bast_records'];

        return collect($tables)
            ->mapWithKeys(fn (string $table): array => [$table => (int) DB::table($table)->count()])
            ->all();
    }

    private function buildXlsx(string $path): void
    {
        $writer = new XlsxWriter;
        $writer->openToFile($path);

        $sheetNames = ['data_konsumen', 'bi_checking', 'psjb', 'pemberkasan', 'proses_bank', 'ppjb_dev', 'akad', 'bast'];
        $firstSheet = $writer->getSheets()[0] ?? null;
        if ($firstSheet !== null) {
            $firstSheet->setName($sheetNames[0]);
            $this->writeSheet($writer, $sheetNames[0]);
            array_shift($sheetNames);
        } else {
            $sheet = $writer->addNewSheetAndMakeItCurrent();
            $sheet->setName($sheetNames[0]);
            $this->writeSheet($writer, $sheetNames[0]);
            array_shift($sheetNames);
        }

        foreach ($sheetNames as $sheetName) {
            $sheet = $writer->addNewSheetAndMakeItCurrent();
            $sheet->setName($sheetName);
            $this->writeSheet($writer, $sheetName);
        }

        $writer->close();
    }

    private function writeSheet(XlsxWriter $writer, string $sheetName): void
    {
        $rows = [];
        $handle = fopen($this->source.'/'.$sheetName.'.csv', 'rb');
        while (($row = fgetcsv($handle, escape: '')) !== false) {
            $rows[] = $row;
        }
        fclose($handle);

        $writer->addRows(array_map(fn (array $values): Row => Row::fromValues($values), $rows));
    }
}
