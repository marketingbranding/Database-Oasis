<?php

namespace App\Services;

use App\Models\Bank;
use App\Models\LegacyMigrationBankMapping;
use App\Models\LegacyMigrationBatch;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class LegacyMigrationBankMappingService
{
    public function approve(
        LegacyMigrationBatch $batch,
        string $rawValue,
        Bank $bank,
        User $user,
        string $reason,
    ): LegacyMigrationBankMapping {
        $normalized = $this->normalize($rawValue);

        if ($normalized === '' || $normalized === 'cash') {
            throw ValidationException::withMessages(['raw_legacy_value' => 'Blank/CASH tidak boleh dipetakan sebagai Bank.']);
        }

        if (! $bank->is_active) {
            throw ValidationException::withMessages(['target_bank_id' => 'Target Bank harus aktif.']);
        }

        return DB::transaction(function () use ($batch, $rawValue, $normalized, $bank, $user, $reason): LegacyMigrationBankMapping {
            return LegacyMigrationBankMapping::create([
                'batch_id' => $batch->id,
                'raw_legacy_value' => trim($rawValue),
                'normalized_alias' => $normalized,
                'target_bank_id' => $bank->id,
                'approved_by' => $user->id,
                'approved_at' => now(),
                'reason' => $reason,
                'source_fingerprint' => $batch->source_fingerprint,
                'audit_fingerprint' => $batch->audit_fingerprint,
            ]);
        });
    }

    public function resolve(LegacyMigrationBatch $batch, ?string $rawValue): ?Bank
    {
        $normalized = $this->normalize($rawValue);
        if ($normalized === '' || $normalized === 'cash') {
            return null;
        }

        $mapping = LegacyMigrationBankMapping::query()
            ->where('batch_id', $batch->id)
            ->where('normalized_alias', $normalized)
            ->where('source_fingerprint', $batch->source_fingerprint)
            ->where('audit_fingerprint', $batch->audit_fingerprint)
            ->first();

        return $mapping?->bank()->where('is_active', true)->first();
    }

    public function normalize(?string $value): string
    {
        return mb_strtolower(preg_replace('/\s+/', ' ', trim($value ?? '')) ?? '');
    }
}
