<?php

namespace App\Services;

use App\Enums\LegacyMigrationPlanOperationType;
use App\Enums\LegacyMigrationPlanStatus;
use App\MigrationReadiness;
use App\Models\Bank;
use App\Models\Consumer;
use App\Models\LegacyMigrationExecution;
use App\Models\LegacyMigrationPlan;
use App\Models\LegacyMigrationProvenance;
use App\Models\Project;
use App\Models\SalesCase;
use App\Models\Unit;
use RuntimeException;

class LegacyMigrationPreflightService
{
    public function __construct(private LegacyMigrationPlanService $plans) {}

    /** @return array<string, int|string> */
    public function verify(LegacyMigrationPlan $plan): array
    {
        if (! in_array($plan->status, [LegacyMigrationPlanStatus::Generated, LegacyMigrationPlanStatus::Approved, LegacyMigrationPlanStatus::Simulated], true)) {
            throw new RuntimeException("Plan status {$plan->status->value} is not executable.");
        }
        if ($this->plans->isStale($plan)) {
            throw new RuntimeException('Source, audit, candidate, or resolution fingerprint changed.');
        }
        if (! hash_equals($plan->plan_fingerprint, $this->plans->calculateFingerprint($plan))) {
            throw new RuntimeException('Immutable plan fingerprint is invalid.');
        }
        if (LegacyMigrationExecution::where('plan_id', $plan->id)->where('plan_fingerprint', $plan->plan_fingerprint)->where('status', 'COMPLETED')->exists()) {
            throw new RuntimeException('This immutable plan was already completed.');
        }

        $operations = $plan->operations()->with('candidate')->orderBy('sequence')->get();
        $candidateIds = $operations->pluck('candidate_id')->filter()->unique();
        $candidates = $plan->batch->candidates()->whereIn('id', $candidateIds)->get();
        if ($candidates->count() !== $candidateIds->count() || $candidates->contains(fn ($candidate): bool => app(LegacyMigrationReadinessService::class)->calculate($candidate) !== MigrationReadiness::Auto)) {
            throw new RuntimeException('Plan contains missing, REVIEW, or BLOCKED candidate.');
        }
        if ($plan->batch->orphans()->where('severity', 'BLOCKING')->where('status', 'PENDING')->exists()) {
            throw new RuntimeException('Unresolved blocking orphan remains.');
        }
        if (LegacyMigrationProvenance::where('plan_id', $plan->id)->exists()) {
            throw new RuntimeException('Plan provenance already exists in target database.');
        }

        $planKeys = $operations->pluck('payload.plan_key')->filter()->all();
        foreach ($operations as $operation) {
            $payload = $operation->payload;
            foreach (['consumer_plan_key', 'unit_plan_key', 'sales_case_plan_key', 'previous_sales_case_plan_key', 'submission_plan_key', 'bank_process_plan_key', 'psjb_plan_key', 'ppjb_plan_key', 'akad_plan_key'] as $key) {
                if (($payload[$key] ?? null) !== null && ! in_array($payload[$key], $planKeys, true)) {
                    throw new RuntimeException("Operation {$operation->sequence} has unresolved {$key}.");
                }
            }
            if ($operation->operation_type === LegacyMigrationPlanOperationType::CreateBankProcess) {
                $bank = Bank::whereKey($payload['bank_id'] ?? null)->where('is_active', true)->first();
                if ($bank === null) {
                    throw new RuntimeException("Operation {$operation->sequence} references invalid canonical Bank.");
                }
            }
            if ($operation->operation_type === LegacyMigrationPlanOperationType::ReuseConsumer && ($payload['source_consumer_plan_key'] ?? null) === null) {
                $consumer = Consumer::find($payload['target_consumer_id'] ?? null);
                if ($consumer === null || $consumer->nik !== ($payload['expected_nik'] ?? null) || ($payload['expected_name'] ?? '') !== $consumer->name) {
                    throw new RuntimeException("Operation {$operation->sequence} Consumer reuse target changed.");
                }
            }
        }

        $operationsByPlanKey = $operations->filter(fn ($operation): bool => isset($operation->payload['plan_key']))->keyBy('payload.plan_key');
        $activeCases = $operations->where('operation_type', LegacyMigrationPlanOperationType::CreateSalesCase)
            ->filter(fn ($operation): bool => ($operation->payload['lifecycle_status'] ?? 'ACTIVE') === 'ACTIVE');
        foreach ($activeCases as $operation) {
            $payload = $operation->payload;
            $consumerPayload = $operationsByPlanKey->get($payload['consumer_plan_key'])->payload;
            $unitPayload = $operationsByPlanKey->get($payload['unit_plan_key'])->payload;
            $nik = $consumerPayload['nik'] ?? $consumerPayload['expected_nik'] ?? null;
            $consumerId = $nik === null ? null : Consumer::where('nik', $nik)->value('id');
            $projectId = Project::where('name', $unitPayload['project_name'] ?? '')->value('id');
            $unit = $projectId === null ? null : Unit::where('project_id', $projectId)->where('unit_code', $unitPayload['unit_code'] ?? '')->first();
            $unitId = $unit?->id;
            if ($unit !== null && $unit->status->value !== 'TERSEDIA') {
                throw new RuntimeException("Operation {$operation->sequence} Unit reuse target conflicts with existing state.");
            }
            if (($consumerId !== null && SalesCase::where('consumer_id', $consumerId)->where('case_status', 'ACTIVE')->exists()) || ($unitId !== null && SalesCase::where('unit_id', $unitId)->where('case_status', 'ACTIVE')->exists())) {
                throw new RuntimeException("Operation {$operation->sequence} conflicts with existing active Sales Case.");
            }
        }

        return [
            'candidates' => $candidateIds->count(),
            'operations' => $operations->count(),
            'review_included' => 0,
            'blocked_included' => 0,
            'blocking_orphans' => 0,
            'environment' => app()->environment(),
        ];
    }
}
