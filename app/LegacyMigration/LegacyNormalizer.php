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

        foreach (['Y-m-d', 'd/m/Y', 'd-m-Y', 'd.m.Y', 'j/n/Y', 'j-n-Y'] as $format) {
            $date = DateTimeImmutable::createFromFormat('!'.$format, $text);
            if ($date instanceof DateTimeImmutable && $date->format($format) === $text) {
                return ['value' => $date->format('Y-m-d'), 'valid' => true, 'empty' => false];
            }
        }

        return ['value' => null, 'valid' => false, 'empty' => false];
    }
}
