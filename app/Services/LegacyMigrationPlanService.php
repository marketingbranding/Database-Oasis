<?php

namespace App\Services;

use App\Enums\LegacyMigrationPlanOperationType;
use App\Enums\LegacyMigrationPlanStatus;
use App\MigrationReadiness;
use App\MigrationReviewDecision;
use App\Models\LegacyMigrationBatch;
use App\Models\LegacyMigrationCandidate;
use App\Models\LegacyMigrationPlan;
use App\Models\LegacyMigrationPlanOperation;
use App\Models\User;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class LegacyMigrationPlanService
{
    public function __construct(
        private LegacyMigrationReadinessService $readiness,
    ) {}

    public function generate(LegacyMigrationBatch $batch, User $user): LegacyMigrationPlan
    {
        $eligible = $batch->candidates()->with(['exceptions', 'reviews', 'resolutions'])->get()->filter(
            fn ($candidate) => $this->readiness->calculate($candidate) === MigrationReadiness::Auto
                && ($candidate->readiness === MigrationReadiness::Auto
                    || $candidate->reviews()->where('decision', MigrationReviewDecision::Accept->value)->exists()),
        );

        $unresolvedBlockingOrphans = $batch->orphans()
            ->where('severity', 'BLOCKING')
            ->where('status', 'PENDING')
            ->exists();

        if ($unresolvedBlockingOrphans) {
            throw ValidationException::withMessages(['orphans' => 'Masih ada blocking orphan yang belum di-account (resolve/link/exclude).']);
        }

        $candidateStateFingerprint = $this->candidateStateFingerprint($batch);
        $reviewResolutionFingerprint = $this->reviewResolutionFingerprint($batch);

        return DB::transaction(function () use ($batch, $user, $eligible, $candidateStateFingerprint, $reviewResolutionFingerprint): LegacyMigrationPlan {
            $plan = LegacyMigrationPlan::create([
                'batch_id' => $batch->id,
                'status' => LegacyMigrationPlanStatus::Generated,
                'source_fingerprint' => $batch->source_fingerprint,
                'audit_fingerprint' => $batch->audit_fingerprint,
                'candidate_state_fingerprint' => $candidateStateFingerprint,
                'review_resolution_fingerprint' => $reviewResolutionFingerprint,
                'summary_totals' => [],
                'generated_by' => $user->id,
                'generated_at' => now(),
                'plan_fingerprint' => '',
            ]);

            $sequence = 0;
            foreach ($eligible as $candidate) {
                $operations = $this->candidateOperations($candidate);
                foreach ($operations as $operationType => $payload) {
                    LegacyMigrationPlanOperation::create([
                        'plan_id' => $plan->id,
                        'candidate_id' => $candidate->id,
                        'orphan_id' => null,
                        'operation_type' => $operationType,
                        'payload' => $payload,
                        'sequence' => ++$sequence,
                        'error' => null,
                    ]);
                }
            }

            $summary = $this->summarize($eligible);
            $planFingerprint = hash('sha256', json_encode([
                'batch_id' => $batch->id,
                'candidate_state' => $candidateStateFingerprint,
                'review_resolution' => $reviewResolutionFingerprint,
                'operations' => LegacyMigrationPlanOperation::query()->where('plan_id', $plan->id)->orderBy('sequence')->pluck('payload')->all(),
            ], JSON_THROW_ON_ERROR));

            $plan->update([
                'summary_totals' => $summary,
                'plan_fingerprint' => $planFingerprint,
            ]);

            return $plan->refresh();
        });
    }

    public function isStale(LegacyMigrationPlan $plan): bool
    {
        return $plan->source_fingerprint !== $plan->batch->source_fingerprint
            || $plan->candidate_state_fingerprint !== $this->candidateStateFingerprint($plan->batch)
            || $plan->review_resolution_fingerprint !== $this->reviewResolutionFingerprint($plan->batch);
    }

    /** @return array<string, array<string, mixed>> */
    private function candidateOperations(LegacyMigrationCandidate $candidate): array
    {
        $case = $candidate->proposed_sales_case;
        $history = $candidate->proposed_history;

        return [
            LegacyMigrationPlanOperationType::CreateConsumer->value => ['key' => $candidate->proposed_consumer['candidate_key'] ?? null],
            LegacyMigrationPlanOperationType::MatchUnit->value => ['key' => $candidate->proposed_unit['candidate_key'] ?? null],
            LegacyMigrationPlanOperationType::CreateSalesCase->value => ['financing' => $candidate->financing_type, 'lifecycle' => $case['lifecycle_status'] ?? null],
            LegacyMigrationPlanOperationType::LinkPreviousCase->value => ['previous' => $case['previous_case_candidate'] ?? null],
            LegacyMigrationPlanOperationType::CreateBiCheck->value => ['rows' => count($history['bi_checking'] ?? [])],
            LegacyMigrationPlanOperationType::CreatePsjb->value => ['rows' => count($history['psjb'] ?? [])],
            LegacyMigrationPlanOperationType::CreateDocumentSubmission->value => ['rows' => count($history['pemberkasan'] ?? [])],
            LegacyMigrationPlanOperationType::CreateBankProcess->value => ['rows' => count($history['proses_bank'] ?? [])],
            LegacyMigrationPlanOperationType::CreateDeveloperPpjb->value => ['rows' => count($history['ppjb_dev'] ?? [])],
            LegacyMigrationPlanOperationType::CreateAkad->value => ['rows' => count($history['akad'] ?? [])],
            LegacyMigrationPlanOperationType::CreateBast->value => ['rows' => count($history['bast'] ?? [])],
            LegacyMigrationPlanOperationType::SetFinalLifecycle->value => ['lifecycle' => $case['lifecycle_status'] ?? null],
            LegacyMigrationPlanOperationType::SetFinalUnitState->value => ['state' => $case['lifecycle_status'] ?? null],
        ];
    }

    /** @param Collection<int, LegacyMigrationCandidate> $eligible
     *  @return array<string, int> */
    private function summarize(Collection $eligible): array
    {
        $totals = ['sales_cases' => $eligible->count()];
        foreach ($eligible as $candidate) {
            $history = $candidate->proposed_history;
            $totals['bi_records'] = ($totals['bi_records'] ?? 0) + count($history['bi_checking'] ?? []);
            $totals['psjb_records'] = ($totals['psjb_records'] ?? 0) + count($history['psjb'] ?? []);
            $totals['pemberkasan_records'] = ($totals['pemberkasan_records'] ?? 0) + count($history['pemberkasan'] ?? []);
            $totals['bank_process_records'] = ($totals['bank_process_records'] ?? 0) + count($history['proses_bank'] ?? []);
            $totals['ppjb_records'] = ($totals['ppjb_records'] ?? 0) + count($history['ppjb_dev'] ?? []);
            $totals['akad_records'] = ($totals['akad_records'] ?? 0) + count($history['akad'] ?? []);
            $totals['bast_records'] = ($totals['bast_records'] ?? 0) + count($history['bast'] ?? []);
        }

        return $totals;
    }

    private function candidateStateFingerprint(LegacyMigrationBatch $batch): string
    {
        return hash('sha256', $batch->candidates()->orderBy('source_candidate_key')->get(['source_candidate_key', 'readiness', 'financing_type', 'lifecycle', 'proposed_history'])->toJson());
    }

    private function reviewResolutionFingerprint(LegacyMigrationBatch $batch): string
    {
        $reviews = $batch->candidates()->with('reviews')->get()->flatMap(fn ($candidate) => $candidate->reviews()->get(['decision', 'reviewed_at', 'source_fingerprint'])->toArray())->values();
        $resolutions = $batch->candidates()->with('resolutions')->get()->flatMap(fn ($candidate) => $candidate->resolutions()->get(['exception_code', 'resolution_type', 'resolved_at'])->toArray())->values();

        return hash('sha256', json_encode(['reviews' => $reviews, 'resolutions' => $resolutions], JSON_THROW_ON_ERROR));
    }
}
