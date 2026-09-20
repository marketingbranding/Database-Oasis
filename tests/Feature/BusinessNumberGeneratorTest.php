<?php

namespace Tests\Feature;

use App\Actions\CreateDeveloperPpjbAction;
use App\Actions\ReissueDeveloperPpjbAction;
use App\DeveloperPpjbStatus;
use App\Enums\BusinessNumberType;
use App\Filament\Resources\AkadRecords\Schemas\AkadRecordForm;
use App\FinancingType;
use App\Models\BankProcess;
use App\Models\Branch;
use App\Models\BusinessNumberSequence;
use App\Models\DeveloperPpjb;
use App\Models\Project;
use App\Models\SalesCase;
use App\Models\Unit;
use App\Models\User;
use App\Services\BusinessNumberGenerator;
use App\Services\SalesCaseStageResolver;
use App\UserRole;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class BusinessNumberGeneratorTest extends TestCase
{
    use RefreshDatabase;

    public function test_akad_ppjb_labels_distinguish_canonical_legacy_and_fallback(): void
    {
        $case = SalesCase::factory()->create();
        $canonical = DeveloperPpjb::factory()->create(['sales_case_id' => $case->id]);
        $canonical->forceFill(['ppjb_code' => 'PPJB-MGL-2026-000001'])->save();
        $legacy = DeveloperPpjb::factory()->create(['sales_case_id' => SalesCase::factory()->create()->id, 'document_number' => '170/UAI/LEGACY']);
        $fallback = DeveloperPpjb::factory()->create(['sales_case_id' => SalesCase::factory()->create()->id, 'document_number' => null, 'document_date' => '2026-09-20']);

        $this->assertSame('PPJB-MGL-2026-000001', AkadRecordForm::ppjbLabel($canonical));
        $this->assertSame('Legacy: 170/UAI/LEGACY', AkadRecordForm::ppjbLabel($legacy));
        $this->assertStringContainsString($fallback->id, AkadRecordForm::ppjbLabel($fallback));
    }

    public function test_sequences_are_independent_by_type_branch_and_year(): void
    {
        $branch = Branch::factory()->create(['code' => 'MGL']);
        $other = Branch::factory()->create(['code' => 'JPR']);
        $generator = app(BusinessNumberGenerator::class);

        $this->assertSame('SP3K-MGL-2026-000001', $generator->next(BusinessNumberType::Sp3k, $branch, 2026));
        $this->assertSame('SP3K-MGL-2026-000002', $generator->next(BusinessNumberType::Sp3k, $branch, 2026));
        $this->assertSame('PPJB-MGL-2026-000001', $generator->next(BusinessNumberType::DeveloperPpjb, $branch, 2026));
        $this->assertSame('SP3K-JPR-2026-000001', $generator->next(BusinessNumberType::Sp3k, $other, 2026));
        $this->assertSame('SP3K-MGL-2027-000001', $generator->next(BusinessNumberType::Sp3k, $branch, 2027));
    }

    public function test_ensure_sp3k_code_is_idempotent_and_ineligible_attempt_consumes_nothing(): void
    {
        $branch = Branch::factory()->create(['code' => 'MGL']);
        $case = SalesCase::factory()->forUnit(Unit::factory()->for(Project::factory()->for($branch))->create())->create();
        $process = BankProcess::factory()->create(['sales_case_id' => $case->id, 'is_authoritative' => true, 'sp3k_date' => '2026-09-20']);
        $generator = app(BusinessNumberGenerator::class);

        $first = $generator->ensureSp3kCode($process);
        $second = $generator->ensureSp3kCode($process);

        $this->assertSame('SP3K-MGL-2026-000001', $first);
        $this->assertSame($first, $second);
        $this->assertSame(1, BusinessNumberSequence::query()->where('type', 'SP3K')->value('last_number'));

        $invalid = BankProcess::factory()->create(['sales_case_id' => SalesCase::factory()->create()->id, 'is_authoritative' => false, 'sp3k_date' => null]);
        try {
            $generator->ensureSp3kCode($invalid);
            $this->fail('Ineligible SP3K unexpectedly received a code.');
        } catch (ValidationException) {
            $this->assertSame(1, BusinessNumberSequence::query()->where('type', 'SP3K')->value('last_number'));
        }
    }

    public function test_ensure_ppjb_code_is_idempotent_and_reissue_gets_new_code(): void
    {
        $this->seed();
        $branch = Branch::factory()->create(['code' => 'MGL']);
        $unit = Unit::factory()->for(Project::factory()->for($branch))->create();
        $actor = User::factory()->create();
        $actor->assignRole(UserRole::HqAdmin);
        $case = SalesCase::factory()->forUnit($unit)->create(['financing_type' => FinancingType::Cash, 'created_by' => $actor->id]);
        $case->documentSubmissions()->create(['submission_date' => now(), 'sequence' => 1, 'status' => 'SUBMITTED', 'type' => 'CASH_INTERNAL']);
        $case->psjbs()->create(['psjb_date' => now(), 'status' => 'ACTIVE']);
        app(SalesCaseStageResolver::class)->reconcile($case);
        $ppjb = app(CreateDeveloperPpjbAction::class)->handle($actor, ['sales_case_id' => $case->id, 'document_date' => '2026-09-20', 'ppjb_code' => 'FORGED']);
        $code = app(BusinessNumberGenerator::class)->ensurePpjbCode($ppjb);
        $this->assertSame($code, app(BusinessNumberGenerator::class)->ensurePpjbCode($ppjb));
        $this->assertSame(1, BusinessNumberSequence::query()->where('type', 'PPJB')->value('last_number'));
        $new = app(ReissueDeveloperPpjbAction::class)->handle($actor, $case, ['document_date' => '2026-09-21']);

        $this->assertSame('PPJB-MGL-2026-000001', $code);
        $this->assertSame($code, $ppjb->refresh()->ppjb_code);
        $this->assertSame('PPJB-MGL-2026-000002', $new->ppjb_code);
        $this->assertSame(DeveloperPpjbStatus::Superseded, $ppjb->status);
        $this->assertNotSame('FORGED', $ppjb->ppjb_code);
    }
}
