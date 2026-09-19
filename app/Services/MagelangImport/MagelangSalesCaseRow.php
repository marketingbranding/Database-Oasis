<?php

namespace App\Services\MagelangImport;

use App\FinancingType;
use App\SalesCaseStage;
use App\SalesCaseStatus;

/**
 * One normalized Magelang source row: 1 id_transaksi_v2 = 1 Sales Case.
 *
 * Only identity/profile fields belong to the consumer; every transactional or
 * milestone field belongs to the sales case and its process tables. Missing
 * intermediate dates are kept as null and never invented.
 *
 * @phpstan-type MagelangRowArray array<string, mixed>
 */
final readonly class MagelangSalesCaseRow
{
    /**
     * @param  list<string>  $anomalies
     */
    private function __construct(
        public ?string $sourceId,
        public ?string $nik,
        public ?string $name,
        public ?string $phone,
        public ?string $email,
        public ?string $address,
        public ?string $occupation,
        public ?string $unitCode,
        public FinancingType $financingType,
        public SalesCaseStatus $status,
        public ?string $bookingDate,
        public ?string $psjbDate,
        public ?string $pemberkasanDate,
        public ?string $bankName,
        public ?string $bankProcessDate,
        public ?string $sp3kNumber,
        public ?string $sp3kDate,
        public ?string $ppjbDate,
        public ?string $akadDate,
        public ?string $bastDate,
        public ?string $closedAt,
        public array $anomalies,
    ) {}

    /**
     * @param  MagelangRowArray  $data
     */
    public static function fromArray(array $data): self
    {
        $text = fn (string $key): ?string => self::text($data[$key] ?? null);
        $date = fn (string $key): ?string => self::date($data[$key] ?? null);

        $rawNik = preg_replace('/\D/', '', (string) ($data['nik'] ?? ''));
        $nik = $rawNik === '' ? null : $rawNik;

        $anomalies = [];

        if ($text('id_transaksi_v2') === null && $text('source_id') === null) {
            $anomalies[] = 'missing_source_id';
        }

        if ($nik === null) {
            $anomalies[] = 'blank_nik';
        } elseif (! preg_match('/^\d{16}$/', $nik)) {
            $anomalies[] = 'malformed_nik';
        }

        $name = $text('name');

        if ($name === null) {
            $anomalies[] = 'missing_name';
        }

        if ($text('unit_code') === null && $text('unitCode') === null) {
            $anomalies[] = 'missing_unit';
        }

        $financing = self::financing($text('financing_type') ?? $text('financingType'));

        if ($financing === null) {
            $anomalies[] = 'unknown_financing';
        }

        [$status, $statusAnomaly] = self::status($text('status'));

        if ($statusAnomaly !== null) {
            $anomalies[] = $statusAnomaly;
        }

        $akadDate = $date('akad_date') ?? $date('akadDate');
        $bastDate = $date('bast_date') ?? $date('bastDate');

        if ($bastDate !== null && $akadDate === null) {
            $anomalies[] = 'bast_without_akad_date';
        }

        $closedAt = match ($status) {
            SalesCaseStatus::Completed => $bastDate,
            SalesCaseStatus::Mundur => $date('withdrawal_date') ?? $date('mundur_date'),
            SalesCaseStatus::Reject => $date('reject_date') ?? $date('rejected_date'),
            default => null,
        };

        if ($status !== SalesCaseStatus::Active && $closedAt === null) {
            $anomalies[] = 'missing_closing_date';
        }

        if (($text('sp3k_number') ?? $text('sp3kNumber')) !== null
            && ($date('sp3k_date') ?? $date('sp3kDate')) === null
            && ($date('bank_process_date') ?? $date('bankProcessDate')) === null) {
            $anomalies[] = 'missing_bank_response_date';
        }

        return new self(
            sourceId: $text('id_transaksi_v2') ?? $text('source_id'),
            nik: $nik !== null && preg_match('/^\d{16}$/', $nik) ? $nik : null,
            name: $name,
            phone: $text('phone'),
            email: $text('email'),
            address: $text('address'),
            occupation: $text('occupation'),
            unitCode: $text('unit_code') ?? $text('unitCode'),
            financingType: $financing ?? FinancingType::KprSubsidi,
            status: $status,
            bookingDate: $date('booking_date') ?? $date('bookingDate'),
            psjbDate: $date('psjb_date') ?? $date('psjbDate'),
            pemberkasanDate: $date('pemberkasan_date') ?? $date('pemberkasanDate'),
            bankName: $text('bank_name') ?? $text('bankName'),
            bankProcessDate: $date('bank_process_date') ?? $date('bankProcessDate'),
            sp3kNumber: $text('sp3k_number') ?? $text('sp3kNumber'),
            sp3kDate: $date('sp3k_date') ?? $date('sp3kDate'),
            ppjbDate: $date('ppjb_date') ?? $date('ppjbDate'),
            akadDate: $akadDate,
            bastDate: $bastDate,
            closedAt: $closedAt,
            anomalies: array_values(array_unique($anomalies)),
        );
    }

    public function hasNikAnomaly(): bool
    {
        return in_array('blank_nik', $this->anomalies, true)
            || in_array('malformed_nik', $this->anomalies, true);
    }

    /**
     * Strongest milestone evidence wins. CASH cases never derive PROSES_BANK.
     * Missing intermediates are skipped, never fabricated.
     */
    public function derivedStage(): SalesCaseStage
    {
        if ($this->bastDate !== null) {
            return SalesCaseStage::Bast;
        }

        if ($this->akadDate !== null) {
            return SalesCaseStage::Akad;
        }

        if ($this->ppjbDate !== null) {
            return SalesCaseStage::PpjbDev;
        }

        if ($this->financingType !== FinancingType::Cash
            && ($this->sp3kDate !== null || $this->sp3kNumber !== null || $this->bankProcessDate !== null)) {
            return SalesCaseStage::ProsesBank;
        }

        if ($this->pemberkasanDate !== null) {
            return SalesCaseStage::Pemberkasan;
        }

        if ($this->psjbDate !== null) {
            return SalesCaseStage::Psjb;
        }

        return SalesCaseStage::DataKonsumen;
    }

    public function hasSp3kEvidence(): bool
    {
        return $this->sp3kNumber !== null || $this->sp3kDate !== null;
    }

    private static function text(mixed $value): ?string
    {
        if (! is_string($value) && ! is_numeric($value)) {
            return null;
        }

        $text = trim((string) $value);

        return $text === '' ? null : $text;
    }

    private static function date(mixed $value): ?string
    {
        $text = self::text($value);

        if ($text === null) {
            return null;
        }

        $timestamp = strtotime($text);

        if ($timestamp === false) {
            return null;
        }

        return date('Y-m-d', $timestamp);
    }

    private static function financing(?string $value): ?FinancingType
    {
        return match (mb_strtoupper((string) $value)) {
            'CASH', 'TUNAI', 'CASH_KERAS' => FinancingType::Cash,
            'KPR', 'KPR_SUBSIDI', 'KPR_SUBSIDI_REGULER', 'KREDIT' => FinancingType::KprSubsidi,
            default => null,
        };
    }

    /**
     * @return array{0: SalesCaseStatus, 1: ?string}
     */
    private static function status(?string $value): array
    {
        return match (mb_strtoupper((string) $value)) {
            'ACTIVE', 'AKTIF' => [SalesCaseStatus::Active, null],
            'COMPLETED', 'SELESAI' => [SalesCaseStatus::Completed, null],
            'MUNDUR' => [SalesCaseStatus::Mundur, null],
            'REJECT', 'REJECTED', 'DITOLAK' => [SalesCaseStatus::Reject, null],
            // Legacy transfer marker: the case itself stays ACTIVE; the move
            // is an event, not a status.
            'PINDAH_KAVLING', 'PINDAH KAVLING' => [SalesCaseStatus::Active, 'legacy_status_pindah_kavling'],
            // Legacy cancellation: closest current equivalent is Mundur.
            'CANCELLED', 'BATAL' => [SalesCaseStatus::Mundur, 'legacy_status_cancelled'],
            '', 'NULL' => [SalesCaseStatus::Active, 'missing_status'],
            default => [SalesCaseStatus::Active, 'unknown_status'],
        };
    }
}
