<?php

namespace App\Services;

use App\BankResponseType;
use App\BiCheckResult;
use App\DocumentSubmissionStatus;
use App\DocumentSubmissionType;
use App\Enums\LegacyMigrationPlanOperationType;
use App\FinancingType;
use App\Models\AkadRecord;
use App\Models\BankProcess;
use App\Models\BastRecord;
use App\Models\BiCheck;
use App\Models\Branch;
use App\Models\Consumer;
use App\Models\DeveloperPpjb;
use App\Models\DocumentSubmission;
use App\Models\LegacyMigrationPlan;
use App\Models\LegacyMigrationPlanOperation;
use App\Models\LegacyMigrationProvenance;
use App\Models\Project;
use App\Models\Psjb;
use App\Models\SalesCase;
use App\Models\Unit;
use App\PsjbStatus;
use App\SalesCaseStage;
use App\SalesCaseStatus;
use App\UnitStatus;
use Illuminate\Support\Facades\DB;
use RuntimeException;

/**
 * Deterministic plan executor. Consumes an immutable plan; never recomputes
 * decisions. Phase 8C.1: disposable/simulation execution only.
 */
class LegacyMigrationImportService
{
    public function __construct(
        private LegacyMigrationPlanService $planService,
    ) {}

    /** @return array<string, mixed> */
    public function execute(LegacyMigrationPlan $plan): array
    {
        if ($this->planService->isStale($plan)) {
            throw new RuntimeException('Plan migration sudah basi (stale). Silakan generate ulang plan.');
        }

        if ($plan->plan_fingerprint !== $this->planService->calculateFingerprint($plan)) {
            throw new RuntimeException('Plan fingerprint tidak cocok (tampered). Import dibatalkan.');
        }

        return DB::transaction(function () use ($plan): array {
            $branch = Branch::firstOrCreate(['code' => 'LEGACY-JEPARA'], ['name' => 'Legacy Jepara', 'city' => 'Jepara', 'province' => 'Jawa Tengah']);

            $counts = [
                'consumers_created' => 0,
                'consumers_reused' => 0,
                'units' => 0,
                'sales_cases' => 0,
                'bi' => 0,
                'psjb' => 0,
                'pemberkasan' => 0,
                'bank' => 0,
                'ppjb' => 0,
                'akad' => 0,
                'bast' => 0,
            ];

            $resolvedConsumers = [];
            $resolvedUnits = [];
            $resolvedCases = [];
            $resolvedPsjbs = [];
            $resolvedSubmissions = [];
            $resolvedBankProcesses = [];
            $resolvedPpjbs = [];
            $resolvedAkads = [];

            $operations = LegacyMigrationPlanOperation::query()
                ->where('plan_id', $plan->id)
                ->orderBy('sequence')
                ->get();

            foreach ($operations as $operation) {
                $payload = $operation->payload;
                $type = $operation->operation_type;

                switch ($type) {
                    case LegacyMigrationPlanOperationType::CreateConsumer:
                        $nik = $payload['nik'] ?? null;
                        if ($nik === null || strlen((string) $nik) !== 16 || str_starts_with((string) $nik, 'LEGACY-')) {
                            throw new RuntimeException("Operational invariant failure: NIK sintesis/invalid tidak diizinkan pada CreateConsumer operation ({$operation->sequence}).");
                        }
                        $consumer = Consumer::create([
                            'nik' => $nik,
                            'name' => $payload['name'],
                            'phone' => $payload['phone'] ?? null,
                        ]);
                        $resolvedConsumers[$payload['plan_key']] = $consumer;
                        $counts['consumers_created']++;
                        break;

                    case LegacyMigrationPlanOperationType::ReuseConsumer:
                        $sourcePlanKey = $payload['source_consumer_plan_key'] ?? null;
                        $targetConsumer = $sourcePlanKey !== null
                            ? ($resolvedConsumers[$sourcePlanKey] ?? null)
                            : Consumer::find($payload['target_consumer_id'] ?? null);
                        if ($targetConsumer === null || ($payload['expected_nik'] !== null && $targetConsumer->nik !== $payload['expected_nik'])) {
                            throw new RuntimeException("Operational invariant failure: Consumer target pada ReuseConsumer tidak cocok ({$operation->sequence}).");
                        }
                        $resolvedConsumers[$payload['plan_key']] = $targetConsumer;
                        $counts['consumers_reused']++;
                        break;

                    case LegacyMigrationPlanOperationType::MatchUnit:
                        $projectName = $payload['project_name'];
                        $unitCode = $payload['unit_code'];
                        $project = Project::firstOrCreate(
                            ['branch_id' => $branch->id, 'name' => $projectName],
                            ['code' => 'P-'.substr(sha1($projectName), 0, 6), 'location' => 'Jepara', 'status' => 'AKTIF']
                        );
                        $unit = Unit::firstOrCreate(
                            ['project_id' => $project->id, 'unit_code' => $unitCode],
                            ['block' => strtok($unitCode, '-'), 'number' => strtok(''), 'status' => UnitStatus::Tersedia->value]
                        );
                        $resolvedUnits[$payload['plan_key']] = $unit;
                        $counts['units']++;
                        break;

                    case LegacyMigrationPlanOperationType::CreateSalesCase:
                        $consumer = $resolvedConsumers[$payload['consumer_plan_key']] ?? throw new RuntimeException("Reference unresolvable: consumer_plan_key {$payload['consumer_plan_key']}");
                        $unit = $resolvedUnits[$payload['unit_plan_key']] ?? throw new RuntimeException("Reference unresolvable: unit_plan_key {$payload['unit_plan_key']}");
                        $financing = $payload['financing_type'] === 'CASH' ? FinancingType::Cash : FinancingType::KprSubsidi;

                        $lifecycle = $payload['lifecycle_status'] ?? 'ACTIVE';
                        $statusAtCreate = match ($lifecycle) {
                            'COMPLETED' => SalesCaseStatus::Completed,
                            'MUNDUR' => SalesCaseStatus::Mundur,
                            'REJECT' => SalesCaseStatus::Reject,
                            'PINDAH_KAVLING' => SalesCaseStatus::PindahKavling,
                            default => SalesCaseStatus::Active,
                        };

                        $salesCase = SalesCase::create([
                            'consumer_id' => $consumer->id,
                            'unit_id' => $unit->id,
                            'project_id' => $unit->project_id,
                            'branch_id' => $branch->id,
                            'financing_type' => $financing,
                            'booking_date' => $payload['booking_date'] ?? null,
                            'current_stage' => $statusAtCreate === SalesCaseStatus::Completed ? SalesCaseStage::Completed : SalesCaseStage::DataKonsumen,
                            'case_status' => $statusAtCreate,
                            'created_by' => $plan->generated_by,
                            'is_legacy_import' => true,
                        ]);
                        $resolvedCases[$payload['plan_key']] = $salesCase;
                        $counts['sales_cases']++;

                        if ($operation->candidate_id !== null && isset($payload['provenance'])) {
                            LegacyMigrationProvenance::create([
                                'batch_id' => $plan->batch_id,
                                'candidate_id' => $operation->candidate_id,
                                'source_sheet' => $payload['provenance']['source_sheet'] ?? 'data_konsumen',
                                'source_row' => $payload['provenance']['source_row'] ?? null,
                                'entity_type' => 'sales_case',
                                'source_values' => $payload,
                                'source_fingerprint' => $plan->source_fingerprint,
                                'audit_fingerprint' => $plan->audit_fingerprint,
                            ]);
                        }
                        break;

                    case LegacyMigrationPlanOperationType::LinkPreviousCase:
                        $case = $resolvedCases[$payload['sales_case_plan_key']] ?? throw new RuntimeException("Reference unresolvable: sales_case_plan_key {$payload['sales_case_plan_key']}");
                        $previousPlanKey = $payload['previous_sales_case_plan_key'] ?? null;
                        $prevCase = $previousPlanKey === null ? null : ($resolvedCases[$previousPlanKey] ?? null);
                        if ($prevCase === null) {
                            throw new RuntimeException("Reference unresolvable: previous_sales_case_plan_key {$previousPlanKey}");
                        }
                        $case->update([
                            'previous_case_id' => $prevCase->id,
                            'transfer_reason' => $payload['reason'],
                        ]);
                        break;

                    case LegacyMigrationPlanOperationType::CreateBiCheck:
                        $case = $resolvedCases[$payload['sales_case_plan_key']] ?? throw new RuntimeException("Reference unresolvable: sales_case_plan_key {$payload['sales_case_plan_key']}");
                        $resNorm = $payload['result'];
                        $resEnum = match ($resNorm) {
                            'CLEAR' => BiCheckResult::Clear,
                            'REVIEW' => BiCheckResult::Review,
                            'REJECTED' => BiCheckResult::Rejected,
                            default => BiCheckResult::Review,
                        };

                        BiCheck::create([
                            'sales_case_id' => $case->id,
                            'check_date' => $payload['check_date'],
                            'result' => $resEnum,
                            'description' => $payload['notes'] ?? null,
                            'is_legacy_import' => true,
                            'legacy_date_missing' => (bool) $payload['legacy_date_missing'],
                        ]);
                        $counts['bi']++;
                        break;

                    case LegacyMigrationPlanOperationType::CreatePsjb:
                        $case = $resolvedCases[$payload['sales_case_plan_key']] ?? throw new RuntimeException("Reference unresolvable: sales_case_plan_key {$payload['sales_case_plan_key']}");
                        $statusEnum = match ($payload['status']) {
                            'SUPERSEDED' => PsjbStatus::Superseded,
                            'CANCELLED' => PsjbStatus::Cancelled,
                            default => PsjbStatus::Active,
                        };

                        $psjb = Psjb::create([
                            'sales_case_id' => $case->id,
                            'psjb_date' => $payload['psjb_date'],
                            'document_number' => $payload['psjb_number'],
                            'status' => $statusEnum,
                            'is_legacy_import' => true,
                            'legacy_date_missing' => (bool) $payload['legacy_date_missing'],
                        ]);
                        $resolvedPsjbs[$payload['plan_key']] = $psjb;
                        $case->update(['current_stage' => SalesCaseStage::Psjb]);
                        $counts['psjb']++;
                        break;

                    case LegacyMigrationPlanOperationType::CreateDocumentSubmission:
                        $case = $resolvedCases[$payload['sales_case_plan_key']] ?? throw new RuntimeException("Reference unresolvable: sales_case_plan_key {$payload['sales_case_plan_key']}");
                        $psjb = isset($payload['psjb_plan_key']) ? ($resolvedPsjbs[$payload['psjb_plan_key']] ?? null) : null;
                        $subType = $payload['type'] === 'CASH_INTERNAL' ? DocumentSubmissionType::CashInternal : DocumentSubmissionType::Bank;

                        $sub = DocumentSubmission::create([
                            'sales_case_id' => $case->id,
                            'psjb_id' => $psjb?->id,
                            'type' => $subType,
                            'bank_id' => $payload['bank_id'],
                            'submission_date' => $payload['submission_date'],
                            'sequence' => $payload['sequence'] ?? 1,
                            'status' => DocumentSubmissionStatus::Submitted,
                            'notes' => $payload['notes'] ?? null,
                            'is_legacy_import' => true,
                            'legacy_date_missing' => (bool) $payload['legacy_date_missing'],
                        ]);
                        $resolvedSubmissions[$payload['plan_key']] = $sub;
                        $case->update(['current_stage' => SalesCaseStage::Pemberkasan]);
                        $counts['pemberkasan']++;
                        break;

                    case LegacyMigrationPlanOperationType::CreateBankProcess:
                        $case = $resolvedCases[$payload['sales_case_plan_key']] ?? throw new RuntimeException("Reference unresolvable: sales_case_plan_key {$payload['sales_case_plan_key']}");
                        if ($case->financing_type === FinancingType::Cash) {
                            throw new RuntimeException("Invariant failure: CASH case cannot carry BankProcess operation ({$operation->sequence}).");
                        }
                        $sub = isset($payload['submission_plan_key']) ? ($resolvedSubmissions[$payload['submission_plan_key']] ?? null) : null;
                        $respEnum = match ($payload['response_type']) {
                            'APPROVED' => BankResponseType::Approved,
                            'REJECTED' => BankResponseType::Rejected,
                            'REVISION' => BankResponseType::Revision,
                            default => BankResponseType::Process,
                        };

                        $bp = BankProcess::create([
                            'sales_case_id' => $case->id,
                            'document_submission_id' => $sub?->id,
                            'bank_id' => $payload['bank_id'] ?? $sub?->bank_id,
                            'response_type' => $respEnum,
                            'response_date' => $payload['response_date'],
                            'sp3k_number' => $payload['sp3k_number'],
                            'sp3k_date' => $payload['sp3k_date'],
                            'is_authoritative' => (bool) $payload['is_authoritative'],
                            'notes' => $payload['notes'] ?? null,
                            'is_legacy_import' => true,
                            'legacy_date_missing' => (bool) $payload['legacy_date_missing'],
                        ]);
                        $resolvedBankProcesses[$payload['plan_key']] = $bp;
                        $case->update(['current_stage' => SalesCaseStage::ProsesBank]);
                        $counts['bank']++;
                        break;

                    case LegacyMigrationPlanOperationType::CreateDeveloperPpjb:
                        $case = $resolvedCases[$payload['sales_case_plan_key']] ?? throw new RuntimeException("Reference unresolvable: sales_case_plan_key {$payload['sales_case_plan_key']}");
                        $bp = isset($payload['bank_process_plan_key']) ? ($resolvedBankProcesses[$payload['bank_process_plan_key']] ?? null) : null;

                        $ppjb = DeveloperPpjb::create([
                            'sales_case_id' => $case->id,
                            'bank_process_id' => $bp?->id,
                            'document_number' => $payload['document_number'],
                            'document_date' => $payload['document_date'],
                            'notes' => $payload['notes'] ?? null,
                            'is_legacy_import' => true,
                            'legacy_date_missing' => (bool) $payload['legacy_date_missing'],
                        ]);
                        $resolvedPpjbs[$payload['plan_key']] = $ppjb;
                        $case->update(['current_stage' => SalesCaseStage::PpjbDev]);
                        $counts['ppjb']++;
                        break;

                    case LegacyMigrationPlanOperationType::CreateAkad:
                        $case = $resolvedCases[$payload['sales_case_plan_key']] ?? throw new RuntimeException("Reference unresolvable: sales_case_plan_key {$payload['sales_case_plan_key']}");
                        $ppjb = isset($payload['ppjb_plan_key']) ? ($resolvedPpjbs[$payload['ppjb_plan_key']] ?? null) : null;
                        if ($payload['akad_date'] === null) {
                            throw new RuntimeException("Invariant failure: Akad date missing ({$operation->sequence}).");
                        }

                        $akad = AkadRecord::create([
                            'sales_case_id' => $case->id,
                            'developer_ppjb_id' => $ppjb?->id,
                            'document_number' => $payload['document_number'],
                            'akad_date' => $payload['akad_date'],
                            'notes' => $payload['notes'] ?? null,
                        ]);
                        $resolvedAkads[$payload['plan_key']] = $akad;
                        $case->update(['current_stage' => SalesCaseStage::Akad]);
                        $counts['akad']++;
                        break;

                    case LegacyMigrationPlanOperationType::CreateBast:
                        $case = $resolvedCases[$payload['sales_case_plan_key']] ?? throw new RuntimeException("Reference unresolvable: sales_case_plan_key {$payload['sales_case_plan_key']}");
                        $akad = isset($payload['akad_plan_key']) ? ($resolvedAkads[$payload['akad_plan_key']] ?? null) : null;
                        if ($payload['bast_date'] === null) {
                            throw new RuntimeException("Invariant failure: BAST date missing ({$operation->sequence}).");
                        }

                        BastRecord::create([
                            'sales_case_id' => $case->id,
                            'akad_id' => $akad?->id,
                            'bast_number' => $payload['bast_number'],
                            'bast_date' => $payload['bast_date'],
                            'status' => $payload['status'] ?? 'COMPLETED',
                            'notes' => $payload['notes'] ?? null,
                        ]);
                        $case->update(['current_stage' => SalesCaseStage::Bast]);
                        $counts['bast']++;
                        break;

                    case LegacyMigrationPlanOperationType::SetFinalLifecycle:
                        $case = $resolvedCases[$payload['sales_case_plan_key']] ?? throw new RuntimeException("Reference unresolvable: sales_case_plan_key {$payload['sales_case_plan_key']}");
                        $status = match ($payload['lifecycle_status']) {
                            'COMPLETED' => SalesCaseStatus::Completed,
                            'MUNDUR' => SalesCaseStatus::Mundur,
                            'REJECT' => SalesCaseStatus::Reject,
                            'PINDAH_KAVLING' => SalesCaseStatus::PindahKavling,
                            default => SalesCaseStatus::Active,
                        };
                        $case->update([
                            'case_status' => $status,
                            'current_stage' => $status === SalesCaseStatus::Completed ? SalesCaseStage::Completed : $case->current_stage,
                        ]);
                        break;

                    case LegacyMigrationPlanOperationType::SetFinalUnitState:
                        $unit = $resolvedUnits[$payload['unit_plan_key']] ?? throw new RuntimeException("Reference unresolvable: unit_plan_key {$payload['unit_plan_key']}");
                        $unitState = match ($payload['state']) {
                            'COMPLETED' => UnitStatus::Terjual->value,
                            'ACTIVE' => UnitStatus::Booking->value,
                            default => UnitStatus::Tersedia->value,
                        };
                        $unit->update(['status' => $unitState]);
                        break;
                }
            }

            return ['counts' => $counts, 'branch_id' => $branch->id];
        });
    }
}
