<?php

namespace Tests\Feature;

use App\BankResponseType;
use App\DeveloperPpjbStatus;
use App\Filament\Resources\AkadRecords\Schemas\AkadRecordForm;
use App\Filament\Resources\SalesCases\SalesCaseResource;
use App\Models\Bank;
use App\Models\BankProcess;
use App\Models\Branch;
use App\Models\DeveloperPpjb;
use App\Models\DocumentSubmission;
use App\Models\Project;
use App\Models\SalesCase;
use App\Models\Unit;
use App\Models\User;
use App\Services\SalesCaseTimelineService;
use App\UserRole;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class IdentifierPresentationTest extends TestCase
{
    use RefreshDatabase;

    public function test_timeline_labels_native_and_legacy_identifiers_honestly(): void
    {
        $this->seed();
        $user = User::factory()->create();
        $user->assignRole(UserRole::HqAdmin);
        $branch = Branch::factory()->create(['code' => 'MGL']);
        $unit = Unit::factory()->for(Project::factory()->for($branch))->create();
        $case = SalesCase::factory()->forUnit($unit)->create(['created_by' => $user->id]);
        $bank = Bank::factory()->create();
        $submission = DocumentSubmission::factory()->create(['sales_case_id' => $case->id, 'bank_id' => $bank->id]);
        $native = BankProcess::factory()->create(['sales_case_id' => $case->id, 'document_submission_id' => $submission->id, 'bank_id' => $bank->id, 'response_type' => BankResponseType::Approved, 'is_authoritative' => true, 'sp3k_code' => 'SP3K-MGL-2026-000001', 'sp3k_date' => '2026-09-20']);
        $legacy = DeveloperPpjb::factory()->create(['sales_case_id' => $case->id, 'document_number' => '170/UAI/LEGACY', 'status' => DeveloperPpjbStatus::Active]);
        $nativePpjb = $case->developerPpjbs()->create(['status' => DeveloperPpjbStatus::Superseded, 'document_date' => now(), 'created_by' => $user->id]);
        $nativePpjb->forceFill(['ppjb_code' => 'PPJB-MGL-2026-000001'])->save();
        $case->akad()->create(['developer_ppjb_id' => $nativePpjb->id, 'akad_date' => now(), 'created_by' => $user->id]);
        $legacyCase = SalesCase::factory()->forUnit(Unit::factory()->for(Project::factory()->for($branch))->create())->create(['created_by' => $user->id]);
        $legacyPpjb = $legacyCase->developerPpjbs()->create(['document_number' => '170/UAI/LEGACY', 'status' => DeveloperPpjbStatus::Active, 'document_date' => now(), 'created_by' => $user->id]);
        $legacyCase->akad()->create(['developer_ppjb_id' => $legacyPpjb->id, 'akad_date' => now(), 'created_by' => $user->id]);

        $items = app(SalesCaseTimelineService::class)->forCase($case->refresh());
        $bankText = implode(' ', $items->where('sourceType', 'bank_process')->flatMap(fn ($item) => $item->descriptionLines)->all());
        $ppjbText = implode(' ', $items->where('sourceType', 'developer_ppjb')->flatMap(fn ($item) => $item->descriptionLines)->all());
        $akadText = implode(' ', $items->where('sourceType', 'akad_record')->flatMap(fn ($item) => $item->descriptionLines)->all());
        $legacyAkadText = implode(' ', app(SalesCaseTimelineService::class)->forCase($legacyCase)->where('sourceType', 'akad_record')->flatMap(fn ($item) => $item->descriptionLines)->all());

        $this->assertStringContainsString('Kode SP3K: SP3K-MGL-2026-000001', $bankText);
        $this->assertStringContainsString('Kode PPJB: PPJB-MGL-2026-000001', $ppjbText);
        $this->assertStringContainsString('Referensi PPJB Legacy: 170/UAI/LEGACY', $ppjbText);
        $this->assertStringContainsString('Kode PPJB: PPJB-MGL-2026-000001', $akadText);
        $this->assertStringContainsString('Referensi PPJB Legacy: 170/UAI/LEGACY', $legacyAkadText);
        $this->assertSame('Legacy: 170/UAI/LEGACY', AkadRecordForm::ppjbLabel($legacy));
        $this->assertNotNull($native->refresh()->sp3k_code);
    }

    public function test_global_search_resolves_canonical_and_legacy_identifiers(): void
    {
        $this->seed();
        $branch = Branch::factory()->create(['code' => 'MGL']);
        $user = User::factory()->create();
        $user->assignRole(UserRole::HqAdmin);
        $makeCase = function (): SalesCase {
            $branch = Branch::where('code', 'MGL')->firstOrFail();

            return SalesCase::factory()->forUnit(Unit::factory()->for(Project::factory()->for($branch))->create())->create();
        };

        $canonicalCase = $makeCase();
        $canonicalBank = Bank::factory()->create();
        $canonicalSubmission = DocumentSubmission::factory()->create(['sales_case_id' => $canonicalCase->id, 'bank_id' => $canonicalBank->id]);
        $canonicalProcess = BankProcess::factory()->create(['sales_case_id' => $canonicalCase->id, 'document_submission_id' => $canonicalSubmission->id, 'bank_id' => $canonicalBank->id]);
        $canonicalProcess->forceFill(['sp3k_code' => 'SP3K-SEARCH-1'])->save();
        $canonicalPpjb = DeveloperPpjb::factory()->create(['sales_case_id' => $canonicalCase->id]);
        $canonicalPpjb->forceFill(['ppjb_code' => 'PPJB-SEARCH-1'])->save();

        $legacyCase = $makeCase();
        $legacyBank = Bank::factory()->create();
        $legacySubmission = DocumentSubmission::factory()->create(['sales_case_id' => $legacyCase->id, 'bank_id' => $legacyBank->id]);
        BankProcess::factory()->create(['sales_case_id' => $legacyCase->id, 'document_submission_id' => $legacySubmission->id, 'bank_id' => $legacyBank->id, 'sp3k_number' => 'LEGACY-SEARCH']);
        DeveloperPpjb::factory()->create(['sales_case_id' => $legacyCase->id, 'document_number' => 'LEGACY-PPJB-SEARCH']);
        $duplicateCase = $makeCase();
        DeveloperPpjb::factory()->create(['sales_case_id' => $duplicateCase->id, 'document_number' => 'LEGACY-PPJB-SEARCH']);

        $this->actingAs($user);
        foreach (['SP3K-SEARCH-1', 'LEGACY-SEARCH', 'PPJB-SEARCH-1', 'LEGACY-PPJB-SEARCH'] as $term) {
            $results = SalesCaseResource::getGlobalSearchResults($term);
            $expectedId = $term === 'SP3K-SEARCH-1' || $term === 'PPJB-SEARCH-1' ? $canonicalCase->id : $legacyCase->id;
            $this->assertTrue($results->contains(fn ($result): bool => str_contains($result->url, $expectedId)), $term.' did not resolve');
        }
        $this->assertSame(2, SalesCaseResource::getGlobalSearchResults('LEGACY-PPJB-SEARCH')->count());
        $this->assertSame($canonicalPpjb->sales_case_id, $canonicalCase->id);
    }

    public function test_legacy_sp3k_timeline_is_explicitly_labeled(): void
    {
        $this->seed();
        $branch = Branch::factory()->create();
        $case = SalesCase::factory()->forUnit(Unit::factory()->for(Project::factory()->for($branch))->create())->create();
        $process = BankProcess::factory()->create(['sales_case_id' => $case->id, 'response_type' => BankResponseType::Approved, 'is_authoritative' => true, 'sp3k_code' => null, 'sp3k_number' => 'LEGACY-SP3K-TEST', 'sp3k_date' => '2026-09-20']);

        $text = implode(' ', app(SalesCaseTimelineService::class)->forCase($case)->flatMap(fn ($item) => $item->descriptionLines)->all());
        $this->assertStringContainsString('Referensi SP3K Legacy: LEGACY-SP3K-TEST', $text);
        $this->assertStringNotContainsString('Kode SP3K: LEGACY-SP3K-TEST', $text);
        $this->assertNull($process->refresh()->sp3k_code);
    }

    public function test_branch_scoped_global_search_excludes_other_branch_identifier(): void
    {
        $this->seed();
        $branchA = Branch::factory()->create();
        $branchB = Branch::factory()->create();
        $user = User::factory()->create(['branch_id' => $branchA->id]);
        $user->assignRole(UserRole::BranchAdmin);
        $caseA = SalesCase::factory()->forUnit(Unit::factory()->for(Project::factory()->for($branchA))->create())->create();
        $caseB = SalesCase::factory()->forUnit(Unit::factory()->for(Project::factory()->for($branchB))->create())->create();
        $bankA = Bank::factory()->create();
        $bankB = Bank::factory()->create();
        $submissionA = DocumentSubmission::factory()->create(['sales_case_id' => $caseA->id, 'bank_id' => $bankA->id]);
        $submissionB = DocumentSubmission::factory()->create(['sales_case_id' => $caseB->id, 'bank_id' => $bankB->id]);
        BankProcess::factory()->create(['sales_case_id' => $caseA->id, 'document_submission_id' => $submissionA->id, 'bank_id' => $bankA->id, 'sp3k_number' => 'BRANCH-A-SP3K']);
        BankProcess::factory()->create(['sales_case_id' => $caseB->id, 'document_submission_id' => $submissionB->id, 'bank_id' => $bankB->id, 'sp3k_number' => 'BRANCH-B-SP3K']);

        $this->actingAs($user);
        $this->assertCount(0, SalesCaseResource::getGlobalSearchResults('BRANCH-B-SP3K'));
        $this->assertTrue(SalesCaseResource::getGlobalSearchResults('BRANCH-A-SP3K')->contains(fn ($result): bool => str_contains($result->url, $caseA->id)));
    }

    public function test_global_search_attributes_include_canonical_and_legacy_identifiers(): void
    {
        $attributes = SalesCaseResource::getGloballySearchableAttributes();
        $this->assertContains(['bankProcesses.sp3k_code'], $attributes);
        $this->assertContains(['bankProcesses.sp3k_number'], $attributes);
        $this->assertContains(['developerPpjbs.ppjb_code'], $attributes);
        $this->assertContains(['developerPpjbs.document_number'], $attributes);
    }
}
