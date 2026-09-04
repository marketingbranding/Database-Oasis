<?php

namespace Tests\Feature;

use App\Models\Bank;
use App\Models\LegacyMigrationBatch;
use App\Models\User;
use App\Services\LegacyMigrationBankMappingService;
use App\Services\LegacyMigrationCandidateService;
use App\UserRole;
use Database\Seeders\BankSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class PhaseEightC3BankMappingTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        ini_set('memory_limit', '2G');
        parent::setUp();
        $this->seed();
    }

    public function test_canonical_bank_seed_is_idempotent_and_separate(): void
    {
        $this->seed(BankSeeder::class);
        $this->seed(BankSeeder::class);

        $this->assertSame(5, Bank::count());
        $this->assertDatabaseHas('banks', ['code' => 'BTN', 'name' => 'BTN']);
        $this->assertDatabaseHas('banks', ['code' => 'BTNS', 'name' => 'BTN Syariah']);
        $this->assertNotSame(Bank::where('code', 'BTN')->value('id'), Bank::where('code', 'BTNS')->value('id'));
    }

    public function test_approved_shared_aliases_map_without_cash_or_blank(): void
    {
        if (! is_file(storage_path('app/private/legacy-audit/jepara/summary.json'))) {
            $this->markTestSkipped('Protected real audit report unavailable.');
        }

        $user = User::factory()->create();
        $user->assignRole(UserRole::HqAdmin);
        $batch = app(LegacyMigrationCandidateService::class)->buildFromReport(storage_path('app/private/legacy-audit/jepara'), $user);
        $service = app(LegacyMigrationBankMappingService::class);

        $this->assertSame('BTN', $service->resolve($batch, 'BTN')->code);
        $this->assertSame('BTNS', $service->resolve($batch, 'BSN')->code);
        $this->assertSame('BRI', $service->resolve($batch, 'BANK BRI')->code);
        $this->assertSame('BJTG', $service->resolve($batch, 'BANK JATENG')->code);
        $this->assertSame('BNI', $service->resolve($batch, 'BNI')->code);
        $this->assertNull($service->resolve($batch, 'CASH'));
        $this->assertNull($service->resolve($batch, null));
        $this->assertDatabaseMissing('banks', ['code' => 'LEGACY-BANK']);
    }

    public function test_shared_alias_is_stale_when_fingerprint_changes(): void
    {
        $user = User::factory()->create();
        $batch = LegacyMigrationBatch::create([
            'source_filename' => 'x.xlsx',
            'source_fingerprint' => 'source-a',
            'audit_fingerprint' => 'audit-a',
            'source_row_counts' => [],
            'status' => 'AUDITED',
            'created_by' => $user->id,
        ]);
        $service = app(LegacyMigrationBankMappingService::class);
        $service->approve($batch, 'BSN', Bank::where('code', 'BTNS')->firstOrFail(), $user, 'approved');

        $this->assertSame('BTNS', $service->resolve($batch, 'BSN')->code);

        $batch->update(['source_fingerprint' => 'source-b']);
        $this->assertNull($service->resolve($batch, 'BSN'));
    }

    public function test_cash_and_blank_cannot_be_approved_as_bank_aliases(): void
    {
        $user = User::factory()->create();
        $batch = LegacyMigrationBatch::create([
            'source_filename' => 'x.xlsx',
            'source_fingerprint' => 'source-a',
            'audit_fingerprint' => 'audit-a',
            'source_row_counts' => [],
            'status' => 'AUDITED',
            'created_by' => $user->id,
        ]);
        $service = app(LegacyMigrationBankMappingService::class);

        foreach (['', 'CASH'] as $value) {
            try {
                $service->approve($batch, $value, Bank::where('code', 'BTN')->firstOrFail(), $user, 'invalid');
                $this->fail('Expected validation exception.');
            } catch (ValidationException) {
                $this->assertTrue(true);
            }
        }
    }
}
