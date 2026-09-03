<?php

namespace App\Services;

use App\BankResponseType;
use App\BiCheckResult;
use App\FinancingType;
use App\Models\AkadRecord;
use App\Models\Bank;
use App\Models\BankProcess;
use App\Models\BastRecord;
use App\Models\BiCheck;
use App\Models\Branch;
use App\Models\Consumer;
use App\Models\DeveloperPpjb;
use App\Models\DocumentSubmission;
use App\Models\LegacyMigrationPlan;
use App\Models\LegacyMigrationProvenance;
use App\Models\Project;
use App\Models\Psjb;
use App\Models\SalesCase;
use App\Models\Unit;
use App\SalesCaseStage;
use App\SalesCaseStatus;
use Illuminate\Support\Facades\DB;

/**
 * Deterministic plan executor. Consumes an immutable plan; never recomputes
 * decisions. Phase 8C: disposable/simulation only.
 */
class LegacyMigrationImportService
{
    /** @return array<string, mixed> */
    public function execute(LegacyMigrationPlan $plan): array
    {
        return DB::transaction(function () use ($plan): array {
            $plan->load('batch.candidates');
            $batch = $plan->batch;

            $branch = Branch::firstOrCreate(['code' => 'LEGACY-JEPARA'], ['name' => 'Legacy Jepara', 'city' => 'Jepara', 'province' => 'Jawa Tengah']);

            $counts = ['consumers_created' => 0, 'consumers_reused' => 0, 'units' => 0, 'sales_cases' => 0, 'bi' => 0, 'psjb' => 0, 'pemberkasan' => 0, 'bank' => 0, 'ppjb' => 0, 'akad' => 0, 'bast' => 0];

            $consumerByNik = [];
            $unitByKey = [];
            $salesCaseByCandidate = [];

            foreach ($batch->candidates()->where('readiness', 'AUTO')->get() as $candidate) {
                $case = $candidate->proposed_sales_case;
                $history = $candidate->proposed_history;
                $nik = $case['nik_normalized'] ?? null;

                $existingConsumer = $nik !== null ? Consumer::where('nik', $nik)->first() : null;
                if ($existingConsumer !== null) {
                    $consumer = $existingConsumer;
                    $counts['consumers_reused']++;
                } elseif ($nik !== null && isset($consumerByNik[$nik])) {
                    $consumer = $consumerByNik[$nik];
                    $counts['consumers_reused']++;
                } else {
                    $consumer = Consumer::create([
                        'nik' => $nik ?? 'LEGACY-'.substr($candidate->source_candidate_key, 0, 24),
                        'name' => $case['name_normalized'] ?? 'Legacy Consumer',
                    ]);
                    if ($nik !== null) {
                        $consumerByNik[$nik] = $consumer;
                    }
                    $counts['consumers_created']++;
                }

                $unitKey = $candidate->proposed_unit['candidate_key'] ?? $candidate->source_candidate_key;
                if (! isset($unitByKey[$unitKey])) {
                    $project = Project::firstOrCreate(['branch_id' => $branch->id, 'name' => $candidate->proposed_unit['project_original'] ?? 'Legacy Project'], ['code' => 'LEGACY-'.substr(sha1($unitKey), 0, 6), 'location' => 'Jepara', 'status' => 'AKTIF']);
                    $unitByKey[$unitKey] = Unit::create([
                        'project_id' => $project->id,
                        'unit_code' => $candidate->proposed_unit['unit_original'] ?? 'U'.substr(sha1($unitKey), 0, 6),
                        'status' => 'TERSEDIA',
                    ]);
                }
                $unit = $unitByKey[$unitKey];
                $counts['units']++;

                $financing = $candidate->financing_type === 'CASH' ? FinancingType::Cash : FinancingType::KprSubsidi;
                $salesCase = SalesCase::create([
                    'consumer_id' => $consumer->id,
                    'unit_id' => $unit->id,
                    'project_id' => $unit->project_id,
                    'branch_id' => $branch->id,
                    'financing_type' => $financing,
                    'current_stage' => SalesCaseStage::Completed,
                    'case_status' => match ($case['lifecycle_status'] ?? 'ACTIVE') {
                        'COMPLETED' => SalesCaseStatus::Completed,
                        'MUNDUR' => SalesCaseStatus::Mundur,
                        'REJECT' => SalesCaseStatus::Reject,
                        'PINDAH_KAVLING' => SalesCaseStatus::PindahKavling,
                        default => SalesCaseStatus::Active,
                    },
                    'created_by' => $plan->generated_by,
                    'is_legacy_import' => true,
                ]);
                $salesCaseByCandidate[$candidate->id] = $salesCase;
                $counts['sales_cases']++;

                for ($i = 0; $i < count($history['bi_checking'] ?? []); $i++) {
                    BiCheck::create(['sales_case_id' => $salesCase->id, 'check_date' => now()->toDateString(), 'result' => BiCheckResult::Clear, 'is_legacy_import' => true, 'legacy_date_missing' => true]);
                    $counts['bi']++;
                }
                $psjb = null;
                for ($i = 0; $i < count($history['psjb'] ?? []); $i++) {
                    $psjb = Psjb::create(['sales_case_id' => $salesCase->id, 'psjb_date' => now()->toDateString(), 'is_legacy_import' => true, 'legacy_date_missing' => true]);
                    $counts['psjb']++;
                }
                $ppjb = null;
                for ($i = 0; $i < count($history['ppjb_dev'] ?? []); $i++) {
                    $ppjb = DeveloperPpjb::create(['sales_case_id' => $salesCase->id, 'document_date' => now()->toDateString(), 'is_legacy_import' => true, 'legacy_date_missing' => true]);
                    $counts['ppjb']++;
                }
                for ($i = 0; $i < count($history['pemberkasan'] ?? []); $i++) {
                    DocumentSubmission::create([
                        'sales_case_id' => $salesCase->id,
                        'psjb_id' => $psjb?->id,
                        'bank_id' => $this->bank()->id,
                        'submission_date' => now()->toDateString(),
                        'sequence' => $i + 1,
                        'is_legacy_import' => true,
                        'legacy_date_missing' => true,
                    ]);
                    $counts['pemberkasan']++;
                }
                for ($i = 0; $i < count($history['proses_bank'] ?? []); $i++) {
                    BankProcess::create(['sales_case_id' => $salesCase->id, 'response_type' => BankResponseType::Process, 'response_date' => now()->toDateString(), 'is_legacy_import' => true, 'legacy_date_missing' => true]);
                    $counts['bank']++;
                }
                if (count($history['akad'] ?? []) > 0 && $ppjb === null) {
                    $ppjb = DeveloperPpjb::create(['sales_case_id' => $salesCase->id, 'document_date' => now()->toDateString(), 'is_legacy_import' => true, 'legacy_date_missing' => true]);
                    $counts['ppjb']++;
                }
                $akad = null;
                for ($i = 0; $i < count($history['akad'] ?? []); $i++) {
                    $akad = AkadRecord::create(['sales_case_id' => $salesCase->id, 'akad_date' => now()->toDateString(), 'developer_ppjb_id' => $ppjb->id]);
                    $counts['akad']++;
                }
                if (count($history['bast'] ?? []) > 0 && $akad === null) {
                    $akad = AkadRecord::create(['sales_case_id' => $salesCase->id, 'akad_date' => now()->toDateString(), 'developer_ppjb_id' => $ppjb->id]);
                    $counts['akad']++;
                }
                if (count($history['bast'] ?? []) > 0) {
                    BastRecord::create(['sales_case_id' => $salesCase->id, 'akad_id' => $akad->id, 'bast_date' => now()->toDateString(), 'status' => 'COMPLETED']);
                    $counts['bast']++;
                }

                LegacyMigrationProvenance::create([
                    'batch_id' => $batch->id,
                    'candidate_id' => $candidate->id,
                    'orphan_id' => null,
                    'source_sheet' => 'data_konsumen',
                    'source_row' => $candidate->proposed_sales_case['process_rows']['data_konsumen'][0] ?? null,
                    'legacy_id' => null,
                    'entity_type' => 'sales_case',
                    'source_values' => $case,
                    'source_fingerprint' => $plan->source_fingerprint,
                    'audit_fingerprint' => $plan->audit_fingerprint,
                ]);
            }

            return ['counts' => $counts, 'branch_id' => $branch->id];
        });
    }

    private function bank(): Bank
    {
        return Bank::firstOrCreate(['code' => 'LEGACY-BANK'], ['name' => 'Legacy Bank']);
    }
}
