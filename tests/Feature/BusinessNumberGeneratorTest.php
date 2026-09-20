<?php

namespace Tests\Feature;

use App\Actions\CreateDeveloperPpjbAction;
use App\Actions\ReissueDeveloperPpjbAction;
use App\DeveloperPpjbStatus;
use App\Enums\BusinessNumberType;
use App\FinancingType;
use App\Models\Branch;
use App\Models\Project;
use App\Models\SalesCase;
use App\Models\Unit;
use App\Models\User;
use App\Services\BusinessNumberGenerator;
use App\Services\SalesCaseStageResolver;
use App\UserRole;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class BusinessNumberGeneratorTest extends TestCase
{
    use RefreshDatabase;

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
        $new = app(ReissueDeveloperPpjbAction::class)->handle($actor, $case, ['document_date' => '2026-09-21']);

        $this->assertSame('PPJB-MGL-2026-000001', $code);
        $this->assertSame($code, $ppjb->refresh()->ppjb_code);
        $this->assertSame('PPJB-MGL-2026-000002', $new->ppjb_code);
        $this->assertSame(DeveloperPpjbStatus::Superseded, $ppjb->status);
        $this->assertNotSame('FORGED', $ppjb->ppjb_code);
    }
}
