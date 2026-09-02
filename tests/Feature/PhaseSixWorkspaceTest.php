<?php

namespace Tests\Feature;

use App\Actions\AdvanceCashCaseToPpjbAction;
use App\Actions\CancelSalesCaseAction;
use App\Actions\CreateAkadAction;
use App\Actions\CreateBastAction;
use App\Actions\CreateCaseNoteAction;
use App\Actions\CreateDeveloperPpjbAction;
use App\Actions\CreateDocumentSubmissionAction;
use App\Actions\CreatePsjbAction;
use App\Actions\CreateSalesCaseAction;
use App\Actions\MarkSalesCaseMundurAction;
use App\Actions\MoveSalesCaseUnitAction;
use App\Actions\RecordBankResponseAction;
use App\Actions\RecordBiCheckAction;
use App\BankResponseType;
use App\BiCheckResult;
use App\Filament\Resources\SalesCases\Pages\ViewSalesCase;
use App\Filament\Resources\SalesCases\SalesCaseResource;
use App\FinancingType;
use App\Models\Bank;
use App\Models\Branch;
use App\Models\CaseNote;
use App\Models\Consumer;
use App\Models\Project;
use App\Models\SalesCase;
use App\Models\Unit;
use App\Models\User;
use App\SalesCaseStage;
use App\SalesCaseStatus;
use App\Services\SalesCaseTimelineService;
use App\Services\TimelineItem;
use App\UnitStatus;
use App\UserRole;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Collection;
use Illuminate\Validation\ValidationException;
use Livewire\Livewire;
use Tests\TestCase;

class PhaseSixWorkspaceTest extends TestCase
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

    // ---------------------------------------------------------------- Workspace

    public function test_active_workspace_renders_with_summary_stepper_and_timeline(): void
    {
        $case = $this->kprCaseAtProsesBank();

        $this->actingAs($this->hq);

        Livewire::test(ViewSalesCase::class, ['record' => $case->id])
            ->assertSuccessful()
            ->assertSeeText($case->consumer->name)
            ->assertSeeText($case->consumer->nik)
            ->assertSeeText($case->unit->unit_code)
            ->assertSeeText($case->current_stage->getLabel())
            ->assertSeeText('Hari di Stage Ini')
            ->assertSeeText('Pemberkasan #2')
            ->assertSeeText('Response Bank BTN — Rejected')
            ->assertSeeText('Response Bank BRI — Approved');
    }

    public function test_workspace_kpr_summary_shows_bank_and_sp3k_without_fabrication(): void
    {
        $case = $this->kprCaseAtProsesBank();

        $items = app(SalesCaseTimelineService::class)->forCase($case);

        $this->assertSame('BRI', $case->latestSubmission->bank->name);
        $this->assertSame('APPROVED', $case->latestBankProcess->response_type->value);
        $this->assertSame('SP3K-BRI-1', $case->currentApprovedBankProcess->sp3k_number);
        $this->assertNotNull($case->daysInCurrentStage());
    }

    public function test_workspace_cash_summary_has_no_fake_bank_or_sp3k(): void
    {
        $case = $this->cashCase();

        $this->assertNull($case->latestSubmission);
        $this->assertNull($case->currentApprovedBankProcess);
        $this->assertDatabaseCount('bank_processes', 0);

        $this->actingAs($this->hq);
        Livewire::test(ViewSalesCase::class, ['record' => $case->id])
            ->assertSuccessful()
            ->assertSeeText('CASH');
    }

    public function test_completed_and_closed_workspaces_render_final_status_cards(): void
    {
        $completed = $this->completedCashCase('BAST-FIN-1');
        $closed = $this->closedCase('Mundur: pindah kerja');

        $this->actingAs($this->hq);

        Livewire::test(ViewSalesCase::class, ['record' => $completed->id])
            ->assertSuccessful()
            ->assertSeeText('Status Akhir')
            ->assertSeeText('Tanggal Akad')
            ->assertSeeText('Tanggal BAST');

        Livewire::test(ViewSalesCase::class, ['record' => $closed->id])
            ->assertSuccessful()
            ->assertSeeText('Status Akhir')
            ->assertSeeText('Mundur: pindah kerja');
    }

    public function test_stepper_reflects_evidence_and_current_stage(): void
    {
        $case = $this->kprCaseAtProsesBank();
        $progress = $case->stageProgress();

        $this->assertSame('done', $progress[SalesCaseStage::DataKonsumen->value]);
        $this->assertSame('done', $progress[SalesCaseStage::BiChecking->value]);
        $this->assertSame('done', $progress[SalesCaseStage::Psjb->value]);
        $this->assertSame('done', $progress[SalesCaseStage::Pemberkasan->value]);
        $this->assertSame('done', $progress[SalesCaseStage::ProsesBank->value]);
        $this->assertSame('current', $progress[SalesCaseStage::PpjbDev->value]);
        $this->assertSame('upcoming', $progress[SalesCaseStage::Akad->value]);
    }

    public function test_quick_action_visibility_follows_eligibility_and_hidden_cannot_bypass(): void
    {
        $case = $this->kprCaseAtProsesBank();
        $completed = $this->completedCashCase('BAST-2');

        $this->actingAs($this->hq);

        Livewire::test(ViewSalesCase::class, ['record' => $case->id])
            ->assertActionVisible('recordBankResponse')
            ->assertActionHidden('createPsjb')
            ->assertActionHidden('createAkad');

        // Completed case: all transactional quick actions hidden.
        Livewire::test(ViewSalesCase::class, ['record' => $completed->id])
            ->assertActionHidden('addBiCheck')
            ->assertActionHidden('createBast')
            ->assertActionHidden('markMundur');

        // Hidden action cannot bypass: domain still rejects post-Akad closure.
        $this->expectException(ValidationException::class);
        app(CancelSalesCaseAction::class)->handle($this->hq, $completed, 'nope');
    }

    public function test_pindah_kavling_navigation_links_both_directions(): void
    {
        $branch = Branch::factory()->create();
        $unitA = Unit::factory()->for(Project::factory()->for($branch))->create();
        $unitB = Unit::factory()->for(Project::factory()->for($branch))->create();

        $old = $this->cashCaseOn($unitA);
        $new = app(MoveSalesCaseUnitAction::class)->handle($this->hq, $old, $unitB->id, 'dekat jalan');

        $this->actingAs($this->hq);

        Livewire::test(ViewSalesCase::class, ['record' => $new->id])
            ->assertSuccessful()
            ->assertSeeText($unitA->unit_code);

        Livewire::test(ViewSalesCase::class, ['record' => $old->id])
            ->assertSuccessful()
            ->assertSeeText($unitB->unit_code);

        $this->assertTrue($old->refresh()->case_status === SalesCaseStatus::PindahKavling);
        $this->assertTrue($new->case_status === SalesCaseStatus::Active);
    }

    public function test_branch_user_cannot_open_cross_branch_workspace_and_auditor_is_read_only(): void
    {
        $case = $this->cashCase();
        $otherBranchAdmin = User::factory()->for(Branch::factory()->create())->create();
        $otherBranchAdmin->assignRole(UserRole::BranchAdmin);

        $this->actingAs($otherBranchAdmin)
            ->get('/admin/sales-cases/'.$case->id)
            ->assertNotFound();

        $auditor = User::factory()->create();
        $auditor->assignRole(UserRole::Auditor);
        $this->actingAs($auditor);
        Livewire::test(ViewSalesCase::class, ['record' => $case->id])->assertSuccessful();
        $this->assertFalse($auditor->can('update', $case));
        $this->assertFalse($auditor->can('create', CaseNote::class));
    }

    // ----------------------------------------------------------------- Timeline

    public function test_timeline_represents_all_records_in_business_date_order(): void
    {
        $case = $this->kprCaseAtProsesBank();

        /** @var Collection<int, TimelineItem> $items */
        $items = app(SalesCaseTimelineService::class)->forCase($case);
        $titles = $items->map(fn ($item): string => $item->title)->values();

        $this->assertContains('Sales Case Dibuat', $titles);
        $this->assertContains('BI Check — Review', $titles);
        $this->assertContains('BI Check — Clear', $titles);
        $this->assertContains('PSJB Dibuat', $titles);
        $this->assertContains('Pemberkasan #1 — BTN', $titles);
        $this->assertContains('Pemberkasan #2 — BRI', $titles);
        $this->assertContains('Response Bank BTN — Rejected', $titles);
        $this->assertContains('Response Bank BRI — Process', $titles);
        $this->assertContains('Response Bank BRI — Approved', $titles);
        $this->assertSame(2, $items->where('sourceType', 'document_submission')->count());
        $this->assertSame(4, $items->where('sourceType', 'bank_process')->count());

        // Business-date ordering, newest first.
        $dates = $items->map(fn ($item): int => $item->date->getTimestamp())->values();
        $sorted = $dates->sortDesc()->values();
        $this->assertSame($sorted->all(), $dates->all());
    }

    public function test_timeline_same_date_tie_break_is_deterministic(): void
    {
        $case = $this->cashCase();
        app(RecordBiCheckAction::class)->handle($this->hq, [
            'sales_case_id' => $case->id, 'check_date' => '2026-09-01', 'result' => BiCheckResult::Clear,
        ]);
        app(CreateCaseNoteAction::class)->handle($this->hq, [
            'sales_case_id' => $case->id, 'note' => 'Same-day note',
        ]);

        $first = app(SalesCaseTimelineService::class)->forCase($case);
        $second = app(SalesCaseTimelineService::class)->forCase($case->refresh());

        $this->assertSame(
            $first->map(fn ($item): ?string => $item->sourceId)->values()->all(),
            $second->map(fn ($item): ?string => $item->sourceId)->values()->all(),
        );
    }

    public function test_duplicate_document_numbers_do_not_merge_timeline_events(): void
    {
        $caseA = $this->completedCashCase('DUP-NUM');
        $caseB = $this->completedCashCase('DUP-NUM');

        $itemsA = app(SalesCaseTimelineService::class)->forCase($caseA);
        $itemsB = app(SalesCaseTimelineService::class)->forCase($caseB);

        $this->assertSame(1, $itemsA->where('sourceType', 'akad_record')->count());
        $this->assertSame(1, $itemsB->where('sourceType', 'akad_record')->count());
        $this->assertNotSame(
            $itemsA->firstWhere('sourceType', 'akad_record')->sourceId,
            $itemsB->firstWhere('sourceType', 'akad_record')->sourceId,
        );
    }

    public function test_cash_timeline_contains_no_fake_bank_events(): void
    {
        $case = $this->completedCashCase('CASH-TL');

        $items = app(SalesCaseTimelineService::class)->forCase($case);

        $this->assertSame(0, $items->where('sourceType', 'bank_process')->count());
        $this->assertSame(0, $items->where('sourceType', 'document_submission')->count());
        $this->assertNotNull($items->firstWhere('sourceType', 'akad_record'));
        $this->assertNotNull($items->firstWhere('sourceType', 'bast_record'));
    }

    // ------------------------------------------------------------------ Search

    public function test_global_search_covers_identity_and_document_numbers(): void
    {
        $case = $this->kprCaseAtProsesBank();
        $this->actingAs($this->hq);

        foreach ([
            $case->consumer->name,
            $case->consumer->nik,
            $case->unit->unit_code,
            'SP3K-BRI-1',
        ] as $term) {
            $results = SalesCaseResource::getGlobalSearchResults($term);
            $this->assertTrue(
                $results->contains(fn ($result): bool => str_contains($result->title, $case->unit->unit_code)),
                "Search [{$term}] did not resolve to the sales case.",
            );
        }
    }

    public function test_duplicate_document_number_returns_multiple_cases(): void
    {
        $caseA = $this->completedCashCase('DUP-SP3K');
        $caseB = $this->completedCashCase('DUP-SP3K');
        $this->actingAs($this->hq);

        $results = SalesCaseResource::getGlobalSearchResults('DUP-SP3K');

        $this->assertSame(2, $results->count());
        $urls = $results->map(fn ($result): string => $result->url)->unique()->values();
        $this->assertSame(2, $urls->count());
    }

    public function test_branch_user_global_search_is_branch_scoped(): void
    {
        $caseA = $this->completedCashCase('BR-ONLY');
        $caseB = $this->completedCashCase('BR-ONLY');

        $branchAdmin = User::factory()->for($caseA->branch)->create();
        $branchAdmin->assignRole(UserRole::BranchAdmin);

        $this->actingAs($branchAdmin);

        $results = SalesCaseResource::getGlobalSearchResults('BR-ONLY');

        $this->assertSame(1, $results->count());
        $this->assertStringContainsString($caseA->id, (string) $results->first()->url);
    }

    // ------------------------------------------------------------------- Notes

    public function test_notes_are_case_scoped_append_only_and_isolated(): void
    {
        $caseA = $this->cashCase();
        $caseB = $this->cashCase();

        app(CreateCaseNoteAction::class)->handle($this->hq, [
            'sales_case_id' => $caseA->id, 'note' => 'Konsumen di luar kota sampai 8 September.',
        ]);

        $this->assertSame(1, $caseA->caseNotes()->count());
        $this->assertSame(0, $caseB->caseNotes()->count());
        $this->assertFalse($this->hq->can('update', $caseA->caseNotes()->firstOrFail()));

        $this->actingAs($this->hq);
        Livewire::test(ViewSalesCase::class, ['record' => $caseA->id])
            ->assertSuccessful()
            ->assertSeeText('Konsumen di luar kota sampai 8 September.');

        $items = app(SalesCaseTimelineService::class)->forCase($caseA->refresh());
        $noteItem = $items->firstWhere('sourceType', 'case_note');
        $this->assertNotNull($noteItem);
        $this->assertSame('NOTE', $noteItem->status);
    }

    public function test_notes_follow_branch_and_role_permissions(): void
    {
        $case = $this->cashCase();
        $otherBranchAdmin = User::factory()->for(Branch::factory()->create())->create();
        $otherBranchAdmin->assignRole(UserRole::BranchAdmin);
        $manager = User::factory()->for($case->branch)->create();
        $manager->assignRole(UserRole::BranchManager);
        $auditor = User::factory()->create();
        $auditor->assignRole(UserRole::Auditor);

        $this->expectValidation(fn () => app(CreateCaseNoteAction::class)->handle($otherBranchAdmin, [
            'sales_case_id' => $case->id, 'note' => 'cross-branch note',
        ]));

        $this->assertFalse($manager->can('create', CaseNote::class));
        $this->assertTrue($manager->can('viewAny', CaseNote::class));
        $this->assertFalse($auditor->can('create', CaseNote::class));
        $this->assertTrue($this->hq->can('create', CaseNote::class));
    }

    // ------------------------------------------------------------------ Aging

    public function test_days_in_current_stage_uses_documented_hierarchy(): void
    {
        $case = $this->newCase(FinancingType::Cash);

        $this->assertSame(0, $case->daysInCurrentStage());

        app(RecordBiCheckAction::class)->handle($this->hq, [
            'sales_case_id' => $case->id,
            'check_date' => now()->subDays(4)->toDateString(),
            'result' => BiCheckResult::Clear,
        ]);
        $case->refresh();
        $this->assertSame(4, $case->daysInCurrentStage());

        app(CreatePsjbAction::class)->handle($this->hq, [
            'sales_case_id' => $case->id,
            'psjb_date' => now()->subDays(2)->toDateString(),
        ]);
        $this->assertSame(2, $case->refresh()->daysInCurrentStage());
    }

    // ---------------------------------------------------------------- Helpers

    private function newCase(FinancingType $type, ?Unit $unit = null): SalesCase
    {
        $unit ??= Unit::factory()->for(Project::factory()->for(Branch::factory()->create()))->create();

        return app(CreateSalesCaseAction::class)->handle($this->hq, [
            'unit_id' => $unit->id,
            'consumer_id' => Consumer::factory()->create()->id,
            'financing_type' => $type,
        ]);
    }

    private function cashCase(): SalesCase
    {
        $case = $this->newCase(FinancingType::Cash);
        app(RecordBiCheckAction::class)->handle($this->hq, [
            'sales_case_id' => $case->id, 'check_date' => now()->toDateString(), 'result' => BiCheckResult::Clear,
        ]);
        app(CreatePsjbAction::class)->handle($this->hq, ['sales_case_id' => $case->id, 'psjb_date' => now()->toDateString()]);
        app(AdvanceCashCaseToPpjbAction::class)->handle($this->hq, $case);

        return $case->refresh();
    }

    private function cashCaseOn(Unit $unit): SalesCase
    {
        $case = $this->newCase(FinancingType::Cash, $unit);
        app(RecordBiCheckAction::class)->handle($this->hq, [
            'sales_case_id' => $case->id, 'check_date' => now()->toDateString(), 'result' => BiCheckResult::Clear,
        ]);
        app(CreatePsjbAction::class)->handle($this->hq, ['sales_case_id' => $case->id, 'psjb_date' => now()->toDateString()]);
        app(AdvanceCashCaseToPpjbAction::class)->handle($this->hq, $case);

        return $case->refresh();
    }

    private function kprCaseAtProsesBank(): SalesCase
    {
        $case = $this->newCase(FinancingType::KprSubsidi);
        $btn = Bank::factory()->create(['name' => 'BTN']);
        $bri = Bank::factory()->create(['name' => 'BRI']);

        app(RecordBiCheckAction::class)->handle($this->hq, [
            'sales_case_id' => $case->id, 'check_date' => '2026-09-01', 'result' => BiCheckResult::Review,
        ]);
        app(RecordBiCheckAction::class)->handle($this->hq, [
            'sales_case_id' => $case->id, 'check_date' => '2026-09-03', 'result' => BiCheckResult::Clear,
        ]);
        app(CreatePsjbAction::class)->handle($this->hq, ['sales_case_id' => $case->id, 'psjb_date' => '2026-09-04']);

        $submission1 = app(CreateDocumentSubmissionAction::class)->handle($this->hq, [
            'sales_case_id' => $case->id, 'bank_id' => $btn->id, 'submission_date' => '2026-09-05',
        ]);
        app(RecordBankResponseAction::class)->handle($this->hq, [
            'sales_case_id' => $case->id, 'document_submission_id' => $submission1->id,
            'bank_id' => $btn->id, 'response_type' => BankResponseType::Process, 'response_date' => '2026-09-06',
        ]);
        app(RecordBankResponseAction::class)->handle($this->hq, [
            'sales_case_id' => $case->id, 'document_submission_id' => $submission1->id,
            'bank_id' => $btn->id, 'response_type' => BankResponseType::Rejected, 'response_date' => '2026-09-07',
        ]);

        $submission2 = app(CreateDocumentSubmissionAction::class)->handle($this->hq, [
            'sales_case_id' => $case->id, 'bank_id' => $bri->id, 'submission_date' => '2026-09-08',
        ]);
        app(RecordBankResponseAction::class)->handle($this->hq, [
            'sales_case_id' => $case->id, 'document_submission_id' => $submission2->id,
            'bank_id' => $bri->id, 'response_type' => BankResponseType::Process, 'response_date' => '2026-09-09',
        ]);
        app(RecordBankResponseAction::class)->handle($this->hq, [
            'sales_case_id' => $case->id, 'document_submission_id' => $submission2->id,
            'bank_id' => $bri->id, 'response_type' => BankResponseType::Approved,
            'response_date' => '2026-09-10', 'sp3k_number' => 'SP3K-BRI-1', 'sp3k_date' => '2026-09-10',
        ]);

        $case->refresh();
        $this->assertTrue($case->current_stage === SalesCaseStage::PpjbDev);

        return $case;
    }

    private function completedCashCase(string $bastNumber): SalesCase
    {
        $case = $this->cashCase();
        $ppjb = app(CreateDeveloperPpjbAction::class)->handle($this->hq, [
            'sales_case_id' => $case->id, 'document_date' => now()->toDateString(),
        ]);
        $akad = app(CreateAkadAction::class)->handle($this->hq, [
            'sales_case_id' => $case->id, 'developer_ppjb_id' => $ppjb->id, 'akad_date' => now()->toDateString(),
        ]);
        app(CreateBastAction::class)->handle($this->hq, [
            'sales_case_id' => $case->id, 'akad_id' => $akad->id,
            'bast_date' => now()->toDateString(), 'bast_number' => $bastNumber,
        ]);

        $case = $case->refresh();
        $this->assertTrue($case->case_status === SalesCaseStatus::Completed);
        $this->assertTrue($case->unit->status === UnitStatus::Terjual);

        return $case;
    }

    private function closedCase(string $reason): SalesCase
    {
        $case = $this->cashCase();
        app(MarkSalesCaseMundurAction::class)->handle($this->hq, $case, $reason);

        return $case->refresh();
    }

    private function expectValidation(callable $callback): void
    {
        try {
            $callback();
            $this->fail('Expected validation exception.');
        } catch (ValidationException) {
            $this->assertTrue(true);
        }
    }
}
