<?php

namespace App\Services\MagelangImport;

use App\BankResponseType;
use App\BastStatus;
use App\DeveloperPpjbStatus;
use App\DocumentSubmissionStatus;
use App\DocumentSubmissionType;
use App\FinancingType;
use App\Models\AkadRecord;
use App\Models\Bank;
use App\Models\BankProcess;
use App\Models\BastRecord;
use App\Models\Branch;
use App\Models\Consumer;
use App\Models\DeveloperPpjb;
use App\Models\DocumentSubmission;
use App\Models\Psjb;
use App\Models\SalesCase;
use App\Models\Unit;
use App\Models\User;
use App\PsjbStatus;
use App\SalesCaseStatus;
use App\UnitStatus;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Support\Facades\DB;

/**
 * Minimal Magelang importer: 1 id_transaksi_v2 = 1 Sales Case.
 *
 * Principles: import every row whose unit can be resolved (relational
 * integrity is the only hard requirement); never fabricate NIK values or
 * milestone dates; never merge sales cases; flag every anomaly with the
 * PERLU DICEK marker instead of dropping the row. One-shot, non-idempotent:
 * run profile() first, fix unit mappings, then import once.
 *
 * @phpstan-type MagelangReport array{imported: list<string>, failed: list<array{source_id: ?string, error: string}>, anomalies: array<string, int>}
 */
final class MagelangImporter
{
    public function __construct(private Branch $branch, private ?User $actor = null) {}

    /**
     * Dry run: normalize every row and count anomalies without writing.
     *
     * @param  list<array<string, mixed>>  $rows
     * @return array{rows: int, sales_cases: int, anomalies: array<string, int>, unit_codes: list<string>}
     */
    public function profile(array $rows): array
    {
        $anomalies = [];
        $unitCodes = [];

        foreach ($rows as $data) {
            $row = MagelangSalesCaseRow::fromArray($data);

            foreach ($row->anomalies as $code) {
                $anomalies[$code] = ($anomalies[$code] ?? 0) + 1;
            }

            if ($row->unitCode !== null) {
                $unitCodes[$row->unitCode] = true;
            }
        }

        $missingUnits = $this->missingUnitCodes(array_keys($unitCodes));

        foreach ($missingUnits as $code) {
            $anomalies['unknown_unit'] = ($anomalies['unknown_unit'] ?? 0) + 1;
        }

        return [
            'rows' => count($rows),
            'sales_cases' => count($rows),
            'anomalies' => $anomalies,
            'unit_codes' => array_keys($unitCodes),
        ];
    }

    /**
     * @param  list<array<string, mixed>>  $rows
     * @return MagelangReport
     */
    public function import(array $rows): array
    {
        $report = ['imported' => [], 'failed' => [], 'anomalies' => []];
        $count = fn (string $code): int => $report['anomalies'][$code] = ($report['anomalies'][$code] ?? 0) + 1;

        $units = $this->unitsByCode();
        $claimedUnitIds = [];

        foreach ($rows as $index => $data) {
            $row = MagelangSalesCaseRow::fromArray($data);

            foreach ($row->anomalies as $code) {
                $count($code);
            }

            $unit = $row->unitCode !== null ? ($units[$row->unitCode] ?? null) : null;

            if ($unit === null) {
                $count('unknown_unit');
                $report['failed'][] = ['source_id' => $row->sourceId, 'error' => "Unit '{$row->unitCode}' tidak ditemukan di cabang; perbaiki pemetaan unit lalu impor ulang baris ini."];

                continue;
            }

            if ($row->status === SalesCaseStatus::Active
                && ($claimedUnitIds[$unit->id] ?? false || $this->unitHasActiveCase($unit))) {
                $count('unit_conflict');
                $report['failed'][] = ['source_id' => $row->sourceId, 'error' => "Unit '{$row->unitCode}' sudah ditempati sales case ACTIVE lain; selesaikan konflik unit lalu impor ulang baris ini."];

                continue;
            }

            try {
                [$case, $finalAnomalies] = DB::transaction(fn (): array => $this->importRow($row, $unit, $index));
            } catch (UniqueConstraintViolationException $e) {
                $count('unit_conflict');
                $report['failed'][] = ['source_id' => $row->sourceId, 'error' => 'Konflik unit ACTIVE yang bersamaan: '.$e->getMessage()];

                continue;
            }

            foreach (array_diff($finalAnomalies, $row->anomalies) as $code) {
                $count($code);
            }

            if ($row->status === SalesCaseStatus::Active) {
                $claimedUnitIds[$unit->id] = true;
            }

            $report['imported'][] = $case->id;
        }

        $this->refreshUnitStatuses(array_values($units));

        return $report;
    }

    /**
     * @return array{0: SalesCase, 1: list<string>} the case and its final anomalies
     */
    private function importRow(MagelangSalesCaseRow $row, Unit $unit, int $index): array
    {
        $profileAnomalies = [];
        $consumer = $this->resolveConsumer($row, $index, $profileAnomalies);

        $bank = $row->bankName !== null ? $this->resolveBank($row) : null;

        $rowAnomalies = array_merge($row->anomalies, $profileAnomalies);

        if ($row->bankName !== null && $bank === null) {
            $rowAnomalies[] = 'bank_unmapped';
        }

        $isCash = $row->financingType === FinancingType::Cash;

        /** @var SalesCase $case */
        $case = SalesCase::create([
            'consumer_id' => $consumer->id,
            'unit_id' => $unit->id,
            'project_id' => $unit->project_id,
            'branch_id' => $this->branch->id,
            'financing_type' => $row->financingType,
            'booking_date' => $row->bookingDate,
            'source' => 'Migrasi Magelang',
            'current_stage' => $row->derivedStage(),
            'case_status' => $row->status,
            'closed_at' => $row->status === SalesCaseStatus::Active ? null : now(),
            'closed_reason' => $row->status === SalesCaseStatus::Active ? null : 'Migrasi Magelang',
            'needs_review' => $rowAnomalies !== [],
            'needs_review_reason' => $rowAnomalies === [] ? null : $this->reason($row, $rowAnomalies),
            'created_by' => $this->actor?->id,
            'is_legacy_import' => true,
        ]);

        $psjb = $row->psjbDate !== null
            ? Psjb::create([
                'sales_case_id' => $case->id,
                'psjb_date' => $row->psjbDate,
                'status' => PsjbStatus::Active,
                'created_by' => $this->actor?->id,
                'is_legacy_import' => true,
            ])
            : null;

        $submission = $row->pemberkasanDate !== null
            ? DocumentSubmission::create([
                'sales_case_id' => $case->id,
                'psjb_id' => $psjb?->id,
                'bank_id' => $isCash ? null : $bank?->id,
                'submission_date' => $row->pemberkasanDate,
                'sequence' => 1,
                'status' => DocumentSubmissionStatus::Submitted,
                'type' => $isCash ? DocumentSubmissionType::CashInternal : DocumentSubmissionType::Bank,
                'created_by' => $this->actor?->id,
                'is_legacy_import' => true,
            ])
            : null;

        $authoritative = null;

        if (! $isCash && ($row->bankProcessDate !== null || $row->hasSp3kEvidence())) {
            /** @var BankProcess $authoritative */
            $authoritative = BankProcess::create([
                'sales_case_id' => $case->id,
                'document_submission_id' => $submission?->id,
                'bank_id' => $bank?->id,
                'response_type' => $row->hasSp3kEvidence() ? BankResponseType::Approved : BankResponseType::Process,
                'response_date' => $row->sp3kDate ?? $row->bankProcessDate,
                'sp3k_number' => $row->sp3kNumber,
                'sp3k_date' => $row->sp3kDate,
                'is_authoritative' => $row->hasSp3kEvidence(),
                'created_by' => $this->actor?->id,
                'is_legacy_import' => true,
            ]);
        }

        $ppjb = $row->ppjbDate !== null
            ? DeveloperPpjb::create([
                'sales_case_id' => $case->id,
                'bank_process_id' => $authoritative?->id,
                'document_date' => $row->ppjbDate,
                'status' => DeveloperPpjbStatus::Active,
                'created_by' => $this->actor?->id,
                'is_legacy_import' => true,
            ])
            : null;

        $akad = null;

        if ($row->akadDate !== null) {
            // A real Akad date with no PPJB evidence keeps its real date; the
            // PPJB placeholder carries an explicit NULL + missing-date flag
            // (never an invented date).
            $ppjb ??= DeveloperPpjb::create([
                'sales_case_id' => $case->id,
                'bank_process_id' => $authoritative?->id,
                'document_date' => null,
                'status' => DeveloperPpjbStatus::Active,
                'notes' => 'PPJB belum tercatat pada sumber migrasi Magelang.',
                'created_by' => $this->actor?->id,
                'is_legacy_import' => true,
                'legacy_date_missing' => true,
            ]);

            /** @var AkadRecord $akad */
            $akad = AkadRecord::create([
                'sales_case_id' => $case->id,
                'developer_ppjb_id' => $ppjb->id,
                'akad_date' => $row->akadDate,
                'created_by' => $this->actor?->id,
            ]);
        }

        if ($row->bastDate !== null && $akad !== null) {
            BastRecord::create([
                'sales_case_id' => $case->id,
                'akad_id' => $akad->id,
                'bast_date' => $row->bastDate,
                'status' => BastStatus::Completed,
                'created_by' => $this->actor?->id,
            ]);
        }

        return [$case->refresh(), $rowAnomalies];
    }

    /**
     * Resolve (never fabricate) the consumer. Valid NIKs are reused without
     * overwriting conflicting profile data; blank/malformed NIKs get a
     * dedicated NULL-NIK consumer per row so the sales case survives.
     *
     * @param  list<string>  $extra  collected profile anomalies (by reference)
     */
    private function resolveConsumer(MagelangSalesCaseRow $row, int $index, ?array &$extra = null): Consumer
    {
        $extra = [];

        if ($row->nik !== null) {
            $existing = Consumer::query()->where('nik', $row->nik)->first();

            if ($existing instanceof Consumer) {
                if ($row->name !== null && $existing->name !== $row->name) {
                    $extra[] = 'conflicting_profile';
                }

                return $existing;
            }

            /** @var Consumer */
            return Consumer::create([
                'nik' => $row->nik,
                'name' => $row->name ?? "Konsumen {$row->sourceId}",
                'phone' => $row->phone,
                'email' => $row->email,
                'address' => $row->address,
                'occupation' => $row->occupation,
            ]);
        }

        // Blank/malformed NIK: never fabricate one, never merge. Each row gets
        // its own consumer so its sales case survives, flagged PERLU DICEK.
        /** @var Consumer */
        return Consumer::create([
            'nik' => null,
            'name' => $row->name ?? "Konsumen {$row->sourceId}".($row->sourceId === null ? " #{$index}" : ''),
            'phone' => $row->phone,
            'email' => $row->email,
            'address' => $row->address,
            'occupation' => $row->occupation,
            'notes' => 'NIK tidak valid pada sumber migrasi Magelang; perlu verifikasi.',
        ]);
    }

    private function resolveBank(MagelangSalesCaseRow $row): ?Bank
    {
        if ($row->bankName === null) {
            return null;
        }

        return Bank::query()->where('name', $row->bankName)->first();
    }

    /**
     * @return array<string, Unit>
     */
    private function unitsByCode(): array
    {
        $units = Unit::query()
            ->whereHas('project', fn ($query) => $query->where('branch_id', $this->branch->id))
            ->get();

        $byCode = [];

        foreach ($units as $unit) {
            $byCode[$unit->unit_code] = $unit;
        }

        return $byCode;
    }

    /**
     * @param  list<string>  $codes
     * @return list<string>
     */
    private function missingUnitCodes(array $codes): array
    {
        if ($codes === []) {
            return [];
        }

        $existing = Unit::query()
            ->whereHas('project', fn ($query) => $query->where('branch_id', $this->branch->id))
            ->whereIn('unit_code', $codes)
            ->pluck('unit_code')
            ->all();

        return array_values(array_diff($codes, $existing));
    }

    private function unitHasActiveCase(Unit $unit): bool
    {
        return SalesCase::query()
            ->where('unit_id', $unit->id)
            ->where('case_status', SalesCaseStatus::Active->value)
            ->exists();
    }

    /**
     * @param  list<string>  $anomalies
     */
    private function reason(MagelangSalesCaseRow $row, array $anomalies): string
    {
        $source = $row->sourceId !== null ? " [{$row->sourceId}]" : '';

        return 'PERLU DICEK'.$source.': '.implode(', ', array_unique($anomalies));
    }

    /**
     * @param  list<Unit>  $units
     */
    private function refreshUnitStatuses(array $units): void
    {
        foreach ($units as $unit) {
            $statuses = SalesCase::query()
                ->where('unit_id', $unit->id)
                ->pluck('case_status')
                ->all();

            if ($statuses === []) {
                continue;
            }

            $status = in_array(SalesCaseStatus::Active->value, $statuses, true)
                ? UnitStatus::Booking
                : (in_array(SalesCaseStatus::Completed->value, $statuses, true)
                    ? UnitStatus::Terjual
                    : UnitStatus::Tersedia);

            Unit::whereKey($unit->id)->update(['status' => $status->value]);
        }
    }
}
