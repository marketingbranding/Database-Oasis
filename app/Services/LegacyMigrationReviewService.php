<?php

namespace App\Services;

use App\Enums\LegacyResolutionType;
use App\Enums\MigrationExceptionSeverity;
use App\MigrationReadiness;
use App\MigrationReviewDecision;
use App\Models\Bank;
use App\Models\LegacyMigrationCandidate;
use App\Models\LegacyMigrationResolution;
use App\Models\LegacyMigrationReview;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/**
 * Review + resolution mutation with fingerprint safety. Decisions and
 * resolutions are append-only by convention (new rows, never silent updates).
 */
class LegacyMigrationReviewService
{
    public function review(LegacyMigrationCandidate $candidate, User $user, MigrationReviewDecision $decision, string $reason): LegacyMigrationReview
    {
        if (app(LegacyMigrationReadinessService::class)->calculate($candidate) !== MigrationReadiness::Review) {
            throw ValidationException::withMessages(['readiness' => 'Hanya kandidat REVIEW yang dapat di-review melalui alur standar.']);
        }

        if ($decision === MigrationReviewDecision::Accept && ! $this->fingerprintMatches($candidate)) {
            throw ValidationException::withMessages(['fingerprint' => 'Source/audit fingerprint tidak cocok dengan batch saat ini.']);
        }

        return DB::transaction(function () use ($candidate, $user, $decision, $reason): LegacyMigrationReview {
            return LegacyMigrationReview::create([
                'candidate_id' => $candidate->id,
                'decision' => $decision,
                'reviewed_by' => $user->id,
                'reviewed_at' => now(),
                'reason' => $reason,
                'source_fingerprint' => $candidate->source_fingerprint,
                'audit_fingerprint' => $candidate->batch->audit_fingerprint,
            ]);
        });
    }

    /**
     * @param  array<string, mixed>|null  $selectedValue
     */
    public function resolveBlockingException(
        LegacyMigrationCandidate $candidate,
        User $user,
        string $exceptionCode,
        LegacyResolutionType $resolutionType,
        string $note,
        ?array $selectedValue = null,
    ): LegacyMigrationResolution {
        if ($candidate->readiness !== MigrationReadiness::Blocked) {
            throw ValidationException::withMessages(['readiness' => 'Hanya kandidat BLOCKED yang dapat menerima resolusi blocker.']);
        }

        $hasException = $candidate->exceptions()
            ->where('code', $exceptionCode)
            ->where('severity', MigrationExceptionSeverity::Blocking)
            ->exists();

        if (! $hasException) {
            throw ValidationException::withMessages(['exception_code' => "Blocker {$exceptionCode} tidak ditemukan pada kandidat ini."]);
        }

        if (! app(LegacyResolutionCompatibilityService::class)->isCompatible($exceptionCode, $resolutionType)) {
            throw ValidationException::withMessages(['resolution_type' => "Resolution {$resolutionType->value} tidak kompatibel dengan {$exceptionCode}."]);
        }

        if ($resolutionType === LegacyResolutionType::MapBank) {
            $bankId = $selectedValue['bank_id'] ?? null;
            if ($bankId === null || ! Bank::whereKey($bankId)->exists()) {
                throw ValidationException::withMessages(['bank_id' => 'MAP_BANK memerlukan bank_id yang menunjuk ke Bank aktif.']);
            }

            $firstException = $candidate->exceptions()->where('code', $exceptionCode)->first();
            if ($firstException !== null) {
                $selectedValue = array_merge($selectedValue, [
                    'source_sheet' => $firstException->source_sheet,
                    'source_row' => $firstException->source_row,
                    'bank_name' => $firstException->evidence['bank_name'] ?? null,
                ]);
            }
        }

        if (! $this->fingerprintMatches($candidate)) {
            throw ValidationException::withMessages(['fingerprint' => 'Source/audit fingerprint tidak cocok dengan batch saat ini.']);
        }

        return DB::transaction(function () use ($candidate, $user, $exceptionCode, $resolutionType, $note, $selectedValue): LegacyMigrationResolution {
            return LegacyMigrationResolution::create([
                'candidate_id' => $candidate->id,
                'exception_code' => $exceptionCode,
                'resolution_type' => $resolutionType->value,
                'selected_value' => $selectedValue,
                'note' => $note,
                'resolved_by' => $user->id,
                'resolved_at' => now(),
                'source_fingerprint' => $candidate->source_fingerprint,
                'audit_fingerprint' => $candidate->batch->audit_fingerprint,
            ]);
        });
    }

    public function fingerprintMatches(LegacyMigrationCandidate $candidate): bool
    {
        $batchSourceFingerprint = (string) $candidate->batch()->value('source_fingerprint');

        return hash_equals($candidate->source_fingerprint, $batchSourceFingerprint);
    }
}
