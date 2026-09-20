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
        $case->developerPpjbs()->create(['sales_case_id' => $case->id, 'ppjb_code' => 'PPJB-MGL-2026-000001', 'status' => DeveloperPpjbStatus::Superseded, 'document_date' => now(), 'created_by' => $user->id]);

        $items = app(SalesCaseTimelineService::class)->forCase($case->refresh());
        $bankText = implode(' ', $items->where('sourceType', 'bank_process')->flatMap(fn ($item) => $item->descriptionLines)->all());
        $ppjbText = implode(' ', $items->where('sourceType', 'developer_ppjb')->flatMap(fn ($item) => $item->descriptionLines)->all());

        $this->assertStringContainsString('Kode SP3K: SP3K-MGL-2026-000001', $bankText);
        $this->assertStringContainsString('Referensi PPJB Legacy: 170/UAI/LEGACY', $ppjbText);
        $this->assertSame('Legacy: 170/UAI/LEGACY', AkadRecordForm::ppjbLabel($legacy));
        $this->assertNotNull($native->refresh()->sp3k_code);
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
