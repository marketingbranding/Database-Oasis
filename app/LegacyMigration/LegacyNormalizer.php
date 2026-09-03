<?php

namespace App\LegacyMigration;

use DateTimeImmutable;
use DateTimeInterface;
use Illuminate\Support\Str;

class LegacyNormalizer
{
    /** @var array<string, string> */
    private const HEADER_ALIASES = [
        'id' => 'legacy_id',
        'legacy_id' => 'legacy_id',
        'id_konsumen' => 'legacy_consumer_id',
        'consumer_id' => 'legacy_consumer_id',
        'id_kons' => 'legacy_consumer_id',
        'id_kavling' => 'id_kavling',
        'nik' => 'nik',
        'no_ktp' => 'nik',
        'nomor_ktp' => 'nik',
        'nama' => 'name',
        'nama_konsumen' => 'name',
        'name' => 'name',
        'telepon' => 'phone',
        'telp' => 'phone',
        'no_hp' => 'phone',
        'phone' => 'phone',
        'proyek' => 'project',
        'project' => 'project',
        'kode_proyek' => 'project',
        'blok' => 'block',
        'block' => 'block',
        'kavling' => 'unit',
        'unit' => 'unit',
        'unit_code' => 'unit',
        'pembiayaan' => 'financing',
        'tipe_pembiayaan' => 'financing',
        'financing' => 'financing',
        'status' => 'status',
        'hasil' => 'result',
        'result' => 'result',
        'bank' => 'bank',
        'nama_bank' => 'bank',
        'tanggal' => 'date',
        'tgl' => 'date',
        'tanggal_booking' => 'booking_date',
        'booking_date' => 'booking_date',
        'tanggal_bi' => 'bi_date',
        'bi_date' => 'bi_date',
        'tanggal_psjb' => 'psjb_date',
        'psjb_date' => 'psjb_date',
        'tanggal_pemberkasan' => 'submission_date',
        'submission_date' => 'submission_date',
        'tanggal_response' => 'response_date',
        'response_date' => 'response_date',
        'tanggal_sp3k' => 'sp3k_date',
        'sp3k_date' => 'sp3k_date',
        'tanggal_ppjb' => 'ppjb_date',
        'ppjb_date' => 'ppjb_date',
        'tanggal_akad' => 'akad_date',
        'akad_date' => 'akad_date',
        'tanggal_bast' => 'bast_date',
        'bast_date' => 'bast_date',
        'nomor_psjb' => 'psjb_number',
        'no_psjb' => 'psjb_number',
        'psjb_number' => 'psjb_number',
        'nomor_sp3k' => 'sp3k_number',
        'no_sp3k' => 'sp3k_number',
        'sp3k_number' => 'sp3k_number',
        'nomor_ppjb' => 'ppjb_number',
        'no_ppjb' => 'ppjb_number',
        'ppjb_number' => 'ppjb_number',
        'nomor_akad' => 'akad_number',
        'no_akad' => 'akad_number',
        'akad_number' => 'akad_number',
        'nomor_bast' => 'bast_number',
        'no_bast' => 'bast_number',
        'bast_number' => 'bast_number',
        'catatan' => 'notes',
        'keterangan' => 'notes',
        'notes' => 'notes',
        'alasan' => 'reason',
        'reason' => 'reason',
        // Real Jepara workbook (jepara_2026.xlsx) aliases — semantic equivalence:
        'hasil_slik' => 'result',
        'tanggal_slik' => 'bi_date',
        'jenis_respon' => 'result',
        'id_psjb' => 'psjb_number',
        'id_ppjb_dev' => 'ppjb_number',
        'no_ppjb_akad' => 'akad_number',
        'id_berkas' => 'submission_number',
        'tanggal_terima_bank' => 'submission_date',
        'tanggal_ttd_ppjb' => 'ppjb_date',
        'status_konsumen' => 'lifecycle_status',
        'kualitas_akad' => 'akad_quality',
    ];

    /**
     * Observed legacy status values mapped to canonical contract values.
     * Only clear semantic equivalences are aliased; everything else is
     * preserved verbatim so the auditor reports it as UNKNOWN_STATUS_VALUE.
     *
     * SLIK/OJK collectability: KOL 1 (Lancar) = clear, KOL 2 (DPK) = review.
     * KOL 3+ and "NO BIC" are intentionally NOT aliased (ambiguous).
     *
     * @var array<string, string>
     */
    private const STATUS_ALIASES = [
        'OK' => 'CLEAR',
        'KOL 1' => 'CLEAR',
        'KOL 2' => 'REVIEW',
        'REJECT' => 'REJECTED',
        'REVISI' => 'REVISION',
        'LANJUT' => 'ACTIVE',
        'MUNDUR' => 'MUNDUR',
        'PINDAH KAVLING' => 'PINDAH_KAVLING',
    ];

    public function header(string $value): string
    {
        $normalized = Str::of(Str::ascii($value))
            ->lower()
            ->trim()
            ->replaceMatches('/[^a-z0-9]+/', '_')
            ->trim('_')
            ->toString();

        return self::HEADER_ALIASES[$normalized] ?? $normalized;
    }

    public function text(mixed $value): ?string
    {
        if ($value === null) {
            return null;
        }

        $text = trim((string) $value);

        return $text === '' ? null : $text;
    }

    public function comparisonText(mixed $value): ?string
    {
        $text = $this->text($value);

        return $text === null ? null : Str::of(Str::ascii($text))
            ->lower()
            ->replaceMatches('/\s+/', ' ')
            ->trim()
            ->toString();
    }

    /** @return array{value: ?string, valid: bool, empty: bool} */
    public function nik(mixed $value): array
    {
        $original = $this->text($value);
        if ($original === null) {
            return ['value' => null, 'valid' => false, 'empty' => true];
        }

        $normalized = preg_replace('/[\s.\-]+/', '', $original) ?? $original;

        return [
            'value' => $normalized,
            'valid' => preg_match('/^\d{16}$/', $normalized) === 1,
            'empty' => false,
        ];
    }

    public function phone(mixed $value): ?string
    {
        $text = $this->text($value);
        if ($text === null) {
            return null;
        }

        $digits = preg_replace('/\D+/', '', $text) ?? '';
        if (str_starts_with($digits, '62')) {
            return '0'.substr($digits, 2);
        }

        return $digits === '' ? null : $digits;
    }

    public function unit(mixed $project, mixed $block, mixed $unit): string
    {
        $projectValue = Str::upper($this->comparisonText($project) ?? '');
        $blockValue = Str::upper($this->comparisonText($block) ?? '');
        $unitValue = Str::upper($this->comparisonText($unit) ?? '');
        $unitValue = preg_replace('/[\s._\/]+/', '-', $unitValue) ?? $unitValue;

        if ($blockValue !== '' && ! str_starts_with($unitValue, $blockValue)) {
            $unitValue = $blockValue.'-'.$unitValue;
        }

        return trim($projectValue.'|'.$unitValue, '|');
    }

    public function document(mixed $value): ?string
    {
        return $this->text($value);
    }

    /**
     * Canonicalize an observed legacy status value. Unknown values are
     * returned uppercased-but-verbatim so callers can flag them explicitly.
     */
    public function statusValue(mixed $value): ?string
    {
        $text = $this->text($value);
        if ($text === null) {
            return null;
        }

        $upper = Str::upper(trim($text));

        return self::STATUS_ALIASES[$upper] ?? $upper;
    }

    /**
     * Split a legacy `id_kavling` composite ("Marison Pati-A01") into the
     * canonical unit key ("PROJECT|UNIT") on the last hyphen. The workbook's
     * own formulas join on this composite (data_kav: CONCATENATE(proyek,"-",
     * kode_kavling)), so the split boundary is unambiguous.
     */
    public function unitFromIdKavling(string $id): string
    {
        $normalized = Str::upper($this->comparisonText($id) ?? '');
        $normalized = preg_replace('/[\s._\/]+/', '-', $normalized) ?? $normalized;

        $position = strrpos($normalized, '-');
        if ($position === false || ! $this->hasDeterministicUnitSuffix($normalized)) {
            // Preserve the complete raw composite as a distinct candidate key;
            // never guess a project/unit boundary for ambiguous values.
            return 'RAW|'.$normalized;
        }

        $project = trim(substr($normalized, 0, $position), '-');
        $unit = trim(substr($normalized, $position + 1), '-');

        return trim($project.'|'.$unit, '|');
    }

    public function hasDeterministicUnitSuffix(string $id): bool
    {
        $normalized = Str::upper($this->comparisonText($id) ?? '');
        $normalized = preg_replace('/[\s._\/]+/', '-', $normalized) ?? $normalized;

        $suffix = Str::afterLast($normalized, '-');

        return $suffix !== $normalized
            && preg_match('/^[A-Z]{1,4}\d{1,4}[A-Z0-9]*$/', $suffix) === 1;
    }

    /** @return array{value: ?string, valid: bool, empty: bool} */
    public function date(mixed $value): array
    {
        if ($value instanceof DateTimeInterface) {
            return ['value' => $value->format('Y-m-d'), 'valid' => true, 'empty' => false];
        }

        $text = $this->text($value);
        if ($text === null) {
            return ['value' => null, 'valid' => true, 'empty' => true];
        }

        // Excel serial dates (1900 system, epoch offset 25569) — the Google
        // Sheets export emits some cached formula values as raw serials.
        if (is_int($value) || is_float($value) || preg_match('/^\d+(\.\d+)?$/', $text) === 1) {
            $serial = (float) $text;
            if ($serial >= 20000 && $serial <= 60000) {
                $timestamp = (int) round(($serial - 25569) * 86400);
                $date = (new DateTimeImmutable('@'.$timestamp))->setTime(0, 0);

                return ['value' => $date->format('Y-m-d'), 'valid' => true, 'empty' => false];
            }

            return ['value' => null, 'valid' => false, 'empty' => false];
        }

        foreach (['Y-m-d', 'd/m/Y', 'd-m-Y', 'd.m.Y', 'j/n/Y', 'j-n-Y'] as $format) {
            $date = DateTimeImmutable::createFromFormat('!'.$format, $text);
            if ($date instanceof DateTimeImmutable && $date->format($format) === $text) {
                return ['value' => $date->format('Y-m-d'), 'valid' => true, 'empty' => false];
            }
        }

        return ['value' => null, 'valid' => false, 'empty' => false];
    }
}
