<?php

namespace Tests\Feature;

use App\Enums\LegacyResolutionType;
use App\Models\Bank;
use App\Models\LegacyMigrationBatch;
use App\Models\LegacyMigrationCandidate;
use App\Models\User;
use App\Services\LegacyMigrationReviewService;
use App\UserRole;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class PhaseEightC4ResolutionTest extends TestCase
{
    use RefreshDatabase;

    private User $hq;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed();
        $this->hq = User::factory()->create();
        $this->hq->assignRole(UserRole::HqAdmin);
    }

    private function blockedCandidate(): LegacyMigrationCandidate
    {
        $batch = LegacyMigrationBatch::create([
            'source_filename' => 'x.xlsx',
            'source_fingerprint' => 'source-a',
            'audit_fingerprint' => 'audit-a',
            'source_row_counts' => [],
            'status' => 'AUDITED',
            'created_by' => $this->hq->id,
        ]);

        return LegacyMigrationCandidate::create([
            'batch_id' => $batch->id,
            'source_candidate_key' => 'cand-'.uniqid(),
            'proposed_consumer' => ['name' => 'X'],
            'proposed_unit' => ['unit' => 'U1'],
            'proposed_sales_case' => ['lifecycle_status' => 'ACTIVE'],
            'proposed_history' => [],
            'confidence' => 'EXACT',
            'readiness' => 'BLOCKED',
            'lifecycle' => 'ACTIVE',
            'financing_type' => 'KPR_SUBSIDI',
            'source_evidence' => [],
            'source_fingerprint' => 'source-a',
        ]);
    }

    public function test_correct_nik_requires_16_digits(): void
    {
        $candidate = $this->blockedCandidate();
        $candidate->exceptions()->create([
            'code' => 'CONSUMER_NIK_INVALID',
            'severity' => 'BLOCKING',
            'source_sheet' => 'data_konsumen',
            'entity_type' => 'data_konsumen',
            'message' => 'invalid',
            'evidence' => ['nik' => 'bad'],
        ]);

        $this->expectException(ValidationException::class);
        app(LegacyMigrationReviewService::class)->resolveBlockingException(
            $candidate,
            $this->hq,
            'CONSUMER_NIK_INVALID',
            LegacyResolutionType::CorrectNik,
            'found valid NIK in passport',
            ['nik' => '1234'],
        );
    }

    public function test_supply_missing_date_requires_valid_date(): void
    {
        $candidate = $this->blockedCandidate();
        $candidate->exceptions()->create([
            'code' => 'MISSING_PROCESS_DATE',
            'severity' => 'BLOCKING',
            'source_sheet' => 'bast',
            'entity_type' => 'bast',
            'message' => 'missing',
            'evidence' => [],
        ]);

        $this->expectException(ValidationException::class);
        app(LegacyMigrationReviewService::class)->resolveBlockingException(
            $candidate,
            $this->hq,
            'MISSING_PROCESS_DATE',
            LegacyResolutionType::SupplyMissingDate,
            'supplied from archive',
            ['date' => 'not-a-date'],
        );
    }

    public function test_valid_supply_missing_date_resolution_persists(): void
    {
        $candidate = $this->blockedCandidate();
        $candidate->exceptions()->create([
            'code' => 'MISSING_PROCESS_DATE',
            'severity' => 'BLOCKING',
            'source_sheet' => 'bast',
            'entity_type' => 'bast',
            'message' => 'missing',
            'evidence' => [],
        ]);

        $resolution = app(LegacyMigrationReviewService::class)->resolveBlockingException(
            $candidate,
            $this->hq,
            'MISSING_PROCESS_DATE',
            LegacyResolutionType::SupplyMissingDate,
            'supplied from archive',
            ['date' => '2026-01-01'],
        );

        $this->assertSame('SUPPLY_MISSING_DATE', $resolution->resolution_type);
        $this->assertSame('2026-01-01', $resolution->selected_value['date']);
    }

    public function test_mapbank_requires_active_bank_and_fingerprint(): void
    {
        $candidate = $this->blockedCandidate();
        $candidate->exceptions()->create([
            'code' => 'BANK_NOT_FOUND',
            'severity' => 'BLOCKING',
            'source_sheet' => 'pemberkasan',
            'entity_type' => 'pemberkasan',
            'message' => 'not found',
            'evidence' => ['bank_name' => 'X'],
        ]);
        $btns = Bank::where('code', 'BTNS')->firstOrFail();
        // fingerprint mismatch (stored differs from batch)
        $candidate->update(['source_fingerprint' => 'tampered']);

        $this->expectException(ValidationException::class);
        app(LegacyMigrationReviewService::class)->resolveBlockingException(
            $candidate,
            $this->hq,
            'BANK_NOT_FOUND',
            LegacyResolutionType::MapBank,
            'canonical',
            ['bank_id' => $btns->id],
        );
    }
}
