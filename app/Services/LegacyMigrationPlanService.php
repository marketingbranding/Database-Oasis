<?php

namespace App\Services;

use App\Enums\LegacyMigrationPlanOperationType;
use App\Enums\LegacyMigrationPlanStatus;
use App\Enums\LegacyResolutionType;
use App\MigrationReadiness;
use App\MigrationReviewDecision;
use App\Models\Bank;
use App\Models\Consumer;
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
            $plannedConsumersByNik = $this->emptyStringMap();
            $casePlanKeys = $eligible->mapWithKeys(fn (LegacyMigrationCandidate $candidate): array => [
                $candidate->source_candidate_key => 'plan_case_'.$candidate->id,
            ])->all();

            $pendingLinks = [];
            foreach ($eligible as $candidate) {
                $operations = $this->candidateOperations($candidate, $plannedConsumersByNik, $casePlanKeys);
                foreach ($operations as $op) {
                    if ($op['type'] === LegacyMigrationPlanOperationType::LinkPreviousCase) {
                        $pendingLinks[] = ['candidate' => $candidate, 'payload' => $op['payload']];

                        continue;
                    }
                    LegacyMigrationPlanOperation::create([
                        'plan_id' => $plan->id,
                        'candidate_id' => $candidate->id,
                        'orphan_id' => null,
                        'operation_type' => $op['type'],
                        'payload' => $op['payload'],
                        'sequence' => ++$sequence,
                        'error' => null,
                    ]);
                }
            }

            foreach ($pendingLinks as $link) {
                LegacyMigrationPlanOperation::create([
                    'plan_id' => $plan->id,
                    'candidate_id' => $link['candidate']->id,
                    'orphan_id' => null,
                    'operation_type' => LegacyMigrationPlanOperationType::LinkPreviousCase,
                    'payload' => $link['payload'],
                    'sequence' => ++$sequence,
                    'error' => null,
                ]);
            }

            $summary = $this->summarize($eligible);
            $planFingerprint = $this->calculateFingerprint($plan);

            $plan->update([
                'summary_totals' => $summary,
                'plan_fingerprint' => $planFingerprint,
            ]);

            return $plan->refresh();
        });
    }

    public function calculateFingerprint(LegacyMigrationPlan $plan): string
    {
        $ops = LegacyMigrationPlanOperation::query()
            ->where('plan_id', $plan->id)
            ->orderBy('sequence')
            ->get(['sequence', 'operation_type', 'candidate_id', 'payload'])
            ->toArray();

        return hash('sha256', json_encode([
            'batch_id' => $plan->batch_id,
            'source_fingerprint' => $plan->source_fingerprint,
            'audit_fingerprint' => $plan->audit_fingerprint,
            'candidate_state' => $plan->candidate_state_fingerprint,
            'review_resolution' => $plan->review_resolution_fingerprint,
            'operations' => $ops,
        ], JSON_THROW_ON_ERROR));
    }

    public function isStale(LegacyMigrationPlan $plan): bool
    {
        return $plan->source_fingerprint !== $plan->batch->source_fingerprint
            || $plan->candidate_state_fingerprint !== $this->candidateStateFingerprint($plan->batch)
            || $plan->review_resolution_fingerprint !== $this->reviewResolutionFingerprint($plan->batch);
    }

    /**
     * @param  array<string, string>  $plannedConsumersByNik
     *
     * @param-out array<string, string> $plannedConsumersByNik
     *
     * @param  array<string, string>  $casePlanKeys
     * @return array<int, array{type: LegacyMigrationPlanOperationType, payload: array<string, mixed>}>
     */
    private function candidateOperations(LegacyMigrationCandidate $candidate, array &$plannedConsumersByNik, array $casePlanKeys): array
    {
        $ops = [];
        $case = $candidate->proposed_sales_case;
        $history = $candidate->proposed_sales_case['proposed_history'] ?? $candidate->proposed_history ?? [];
        $consumer = $candidate->proposed_consumer;
        $unit = $candidate->proposed_unit;
        $financing = $candidate->financing_type;

        $cid = $candidate->id;
        $consumerPlanKey = 'plan_consumer_'.$cid;
        $unitPlanKey = 'plan_unit_'.$cid;
        $salesCasePlanKey = 'plan_case_'.$cid;

        // 1. Consumer operation
        $nikValue = $case['nik_normalized'] ?? null;
        $nik = is_string($nikValue) && preg_match('/^\d{16}$/', $nikValue) === 1 ? $nikValue : null;
        $existingConsumer = null;
        if ($nik !== null) {
            $existingConsumer = Consumer::where('nik', $nik)->first();
        }

        if ($existingConsumer !== null) {
            $ops[] = [
                'type' => LegacyMigrationPlanOperationType::ReuseConsumer,
                'payload' => [
                    'plan_key' => $consumerPlanKey,
                    'action' => 'REUSE',
                    'target_consumer_id' => $existingConsumer->id,
                    'source_consumer_plan_key' => null,
                    'expected_nik' => $nik,
                    'expected_name' => $consumer['name_original'] ?? $case['name_normalized'] ?? '',
                ],
            ];
        } elseif ($nik !== null && isset($plannedConsumersByNik[$nik])) {
            $ops[] = [
                'type' => LegacyMigrationPlanOperationType::ReuseConsumer,
                'payload' => [
                    'plan_key' => $consumerPlanKey,
                    'action' => 'REUSE',
                    'target_consumer_id' => null,
                    'source_consumer_plan_key' => $plannedConsumersByNik[$nik],
                    'expected_nik' => $nik,
                    'expected_name' => $consumer['name_original'] ?? $case['name_normalized'] ?? '',
                ],
            ];
        } else {
            if ($nik === null) {
                throw ValidationException::withMessages(['consumer' => "Kandidat {$candidate->source_candidate_key} tidak memiliki NIK 16-digit valid untuk pembuatan Consumer baru."]);
            }
            $consumerName = $consumer['name_original'] ?? $case['name_normalized'] ?? '';
            if ($consumerName === '') {
                throw ValidationException::withMessages(['consumer' => "Kandidat {$candidate->source_candidate_key} tidak memiliki nama Consumer; identitas sintetis tidak diizinkan."]);
            }
            $plannedConsumersByNik[$nik] = $consumerPlanKey;
            $ops[] = [
                'type' => LegacyMigrationPlanOperationType::CreateConsumer,
                'payload' => [
                    'plan_key' => $consumerPlanKey,
                    'action' => 'CREATE',
                    'nik' => $nik,
                    'name' => $consumerName,
                    'phone' => $case['phone_normalized'] ?? null,
                ],
            ];
        }

        // 2. Unit operation
        $ops[] = [
            'type' => LegacyMigrationPlanOperationType::MatchUnit,
            'payload' => [
                'plan_key' => $unitPlanKey,
                'project_name' => $unit['project_original'] ?? 'Project '.$candidate->branch_id,
                'unit_code' => $unit['unit_original'] ?? 'Unit',
                'normalized_unit_key' => $unit['normalized_key'] ?? $candidate->source_candidate_key,
            ],
        ];

        // 3. Sales Case operation
        $ops[] = [
            'type' => LegacyMigrationPlanOperationType::CreateSalesCase,
            'payload' => [
                'plan_key' => $salesCasePlanKey,
                'consumer_plan_key' => $consumerPlanKey,
                'unit_plan_key' => $unitPlanKey,
                'financing_type' => $financing,
                'lifecycle_status' => $case['lifecycle_status'] ?? 'ACTIVE',
                'booking_date' => $case['dates']['consumer'] ?? null,
                'provenance' => [
                    'source_sheet' => 'data_konsumen',
                    'source_row' => $case['process_rows']['data_konsumen'][0] ?? null,
                ],
            ],
        ];

        // 4. Link Previous Case if present
        if (! empty($case['previous_case_candidate'])) {
            $previousPlanKey = $casePlanKeys[$case['previous_case_candidate']] ?? null;
            if ($previousPlanKey === null) {
                throw ValidationException::withMessages(['previous_case' => "Kandidat case sebelumnya {$case['previous_case_candidate']} tidak termasuk dalam plan executable."]);
            }

            $ops[] = [
                'type' => LegacyMigrationPlanOperationType::LinkPreviousCase,
                'payload' => [
                    'sales_case_plan_key' => $salesCasePlanKey,
                    'previous_sales_case_plan_key' => $previousPlanKey,
                    'previous_candidate_key' => $case['previous_case_candidate'],
                    'reason' => 'POTENTIAL_PINDAH_KAVLING',
                ],
            ];
        }

        // 5. BI Checks
        $biRows = $this->normalizeHistoryRows($history['bi_checking'] ?? [], 'bi_checking');
        foreach ($biRows as $idx => $bi) {
            $biPlanKey = $salesCasePlanKey.'_bi_'.($bi['source_row'] ?? $idx);
            $ops[] = [
                'type' => LegacyMigrationPlanOperationType::CreateBiCheck,
                'payload' => [
                    'plan_key' => $biPlanKey,
                    'sales_case_plan_key' => $salesCasePlanKey,
                    'source_sheet' => 'bi_checking',
                    'source_row' => $bi['source_row'] ?? null,
                    'check_date' => $bi['date_normalized'] ?? null,
                    'legacy_date_missing' => ($bi['date_normalized'] ?? null) === null,
                    'result' => $bi['result_normalized'] ?? 'CLEAR',
                    'notes' => $bi['notes'] ?? null,
                    'provenance' => $bi,
                ],
            ];
        }

        // 6. PSJBs
        $psjbRows = $this->normalizeHistoryRows($history['psjb'] ?? [], 'psjb');
        $lastPsjbPlanKey = null;
        foreach ($psjbRows as $idx => $psjb) {
            $psjbPlanKey = $salesCasePlanKey.'_psjb_'.($psjb['source_row'] ?? $idx);
            $lastPsjbPlanKey = $psjbPlanKey;
            $ops[] = [
                'type' => LegacyMigrationPlanOperationType::CreatePsjb,
                'payload' => [
                    'plan_key' => $psjbPlanKey,
                    'sales_case_plan_key' => $salesCasePlanKey,
                    'source_sheet' => 'psjb',
                    'source_row' => $psjb['source_row'] ?? null,
                    'psjb_number' => $psjb['psjb_number'] ?? null,
                    'psjb_date' => $psjb['date_normalized'] ?? null,
                    'legacy_date_missing' => ($psjb['date_normalized'] ?? null) === null,
                    'status' => $psjb['status'] ?? 'ACTIVE',
                    'notes' => $psjb['notes'] ?? null,
                    'provenance' => $psjb,
                ],
            ];
        }

        // 7. Pemberkasan / Document Submissions
        $subRows = $this->normalizeHistoryRows($history['pemberkasan'] ?? [], 'pemberkasan');
        $lastSubPlanKey = null;
        $submissionPlanKeysByBank = [];
        if ($financing === 'CASH') {
            foreach ($subRows as $idx => $sub) {
                $cashSubPlanKey = $salesCasePlanKey.'_cash_sub_'.($sub['source_row'] ?? $idx);
                $lastSubPlanKey = $cashSubPlanKey;
                $ops[] = [
                    'type' => LegacyMigrationPlanOperationType::CreateDocumentSubmission,
                    'payload' => [
                        'plan_key' => $cashSubPlanKey,
                        'sales_case_plan_key' => $salesCasePlanKey,
                        'psjb_plan_key' => $lastPsjbPlanKey,
                        'type' => 'CASH_INTERNAL',
                        'bank_id' => null,
                        'bank_name' => null,
                        'submission_date' => $sub['date_normalized'] ?? null,
                        'legacy_date_missing' => ($sub['date_normalized'] ?? null) === null,
                        'sequence' => $sub['sequence'] ?? ($idx + 1),
                        'notes' => $sub['notes'] ?? null,
                        'provenance' => $sub,
                    ],
                ];
            }
        } else {
            foreach ($subRows as $idx => $sub) {
                $subPlanKey = $salesCasePlanKey.'_sub_'.($sub['source_row'] ?? $idx);
                $lastSubPlanKey = $subPlanKey;
                $bankName = $sub['bank_name'] ?? null;
                $bank = $this->resolveBankForCandidate($candidate, $bankName, 'pemberkasan', $sub['source_row'] ?? null);
                if ($bankName !== null) {
                    $submissionPlanKeysByBank[mb_strtolower(trim($bankName))][] = $subPlanKey;
                }
                if ($bankName !== null && $bank === null) {
                    throw ValidationException::withMessages(['bank' => "Bank '{$bankName}' pada kandidat {$candidate->source_candidate_key} tidak ditemukan di database."]);
                }
                $ops[] = [
                    'type' => LegacyMigrationPlanOperationType::CreateDocumentSubmission,
                    'payload' => [
                        'plan_key' => $subPlanKey,
                        'sales_case_plan_key' => $salesCasePlanKey,
                        'psjb_plan_key' => $lastPsjbPlanKey,
                        'type' => 'BANK',
                        'bank_id' => $bank?->id,
                        'bank_name' => $bank !== null ? $bank->name : $bankName,
                        'submission_date' => $sub['date_normalized'] ?? null,
                        'legacy_date_missing' => ($sub['date_normalized'] ?? null) === null,
                        'sequence' => $sub['sequence'] ?? ($idx + 1),
                        'notes' => $sub['notes'] ?? null,
                        'provenance' => $sub,
                    ],
                ];
            }
        }

        // 8. Bank Processes (KPR ONLY)
        $authoritativeBpPlanKey = null;
        if ($financing === 'KPR_SUBSIDI') {
            $bpRows = $this->normalizeHistoryRows($history['proses_bank'] ?? [], 'proses_bank');
            foreach ($bpRows as $idx => $bp) {
                $bpPlanKey = $salesCasePlanKey.'_bp_'.($bp['source_row'] ?? $idx);
                $bankName = $bp['bank_name'] ?? null;
                $bank = $this->resolveBankForCandidate($candidate, $bankName, 'proses_bank', $bp['source_row'] ?? null);
                if ($bankName !== null && $bank === null) {
                    throw ValidationException::withMessages(['bank' => "Bank '{$bankName}' pada kandidat {$candidate->source_candidate_key} tidak ditemukan di database."]);
                }
                $isAuth = (bool) ($bp['is_authoritative'] ?? false);
                if ($isAuth) {
                    $authoritativeBpPlanKey = $bpPlanKey;
                }
                $normalizedBankName = $bankName === null ? null : mb_strtolower(trim($bankName));
                $matchingSubmissionKeys = $normalizedBankName === null ? [] : ($submissionPlanKeysByBank[$normalizedBankName] ?? []);
                $submissionPlanKey = $matchingSubmissionKeys === [] ? null : end($matchingSubmissionKeys);

                $ops[] = [
                    'type' => LegacyMigrationPlanOperationType::CreateBankProcess,
                    'payload' => [
                        'plan_key' => $bpPlanKey,
                        'sales_case_plan_key' => $salesCasePlanKey,
                        'submission_plan_key' => $submissionPlanKey,
                        'source_sheet' => 'proses_bank',
                        'source_row' => $bp['source_row'] ?? null,
                        'bank_id' => $bank?->id,
                        'bank_name' => $bank !== null ? $bank->name : $bankName,
                        'response_type' => $bp['response_normalized'] ?? 'PROCESS',
                        'response_date' => $bp['response_date_normalized'] ?? null,
                        'legacy_date_missing' => ($bp['response_date_normalized'] ?? null) === null,
                        'sp3k_number' => $bp['sp3k_number'] ?? null,
                        'sp3k_date' => $bp['sp3k_date_normalized'] ?? null,
                        'is_authoritative' => $isAuth,
                        'notes' => $bp['notes'] ?? null,
                        'provenance' => $bp,
                    ],
                ];
            }
        }

        // 9. Developer PPJB
        $ppjbRows = $this->normalizeHistoryRows($history['ppjb_dev'] ?? [], 'ppjb_dev');
        $lastPpjbPlanKey = null;
        foreach ($ppjbRows as $idx => $ppjb) {
            $ppjbPlanKey = $salesCasePlanKey.'_ppjb_'.($ppjb['source_row'] ?? $idx);
            $lastPpjbPlanKey = $ppjbPlanKey;
            $ops[] = [
                'type' => LegacyMigrationPlanOperationType::CreateDeveloperPpjb,
                'payload' => [
                    'plan_key' => $ppjbPlanKey,
                    'sales_case_plan_key' => $salesCasePlanKey,
                    'bank_process_plan_key' => $authoritativeBpPlanKey,
                    'source_sheet' => 'ppjb_dev',
                    'source_row' => $ppjb['source_row'] ?? null,
                    'document_number' => $ppjb['document_number'] ?? null,
                    'document_date' => $ppjb['date_normalized'] ?? null,
                    'legacy_date_missing' => ($ppjb['date_normalized'] ?? null) === null,
                    'notes' => $ppjb['notes'] ?? null,
                    'provenance' => $ppjb,
                ],
            ];
        }

        // 10. Akad Records
        $akadRows = $this->normalizeHistoryRows($history['akad'] ?? [], 'akad');
        $lastAkadPlanKey = null;
        foreach ($akadRows as $idx => $akad) {
            $akadPlanKey = $salesCasePlanKey.'_akad_'.($akad['source_row'] ?? $idx);
            $lastAkadPlanKey = $akadPlanKey;
            $akadDate = $akad['date_normalized'] ?? null;
            if ($akadDate === null) {
                throw ValidationException::withMessages(['akad' => "Akad pada row {$akad['source_row']} kandidat {$candidate->source_candidate_key} tidak memiliki tanggal valid."]);
            }

            $ops[] = [
                'type' => LegacyMigrationPlanOperationType::CreateAkad,
                'payload' => [
                    'plan_key' => $akadPlanKey,
                    'sales_case_plan_key' => $salesCasePlanKey,
                    'ppjb_plan_key' => $lastPpjbPlanKey,
                    'source_sheet' => 'akad',
                    'source_row' => $akad['source_row'] ?? null,
                    'document_number' => $akad['document_number'] ?? null,
                    'akad_date' => $akadDate,
                    'legacy_date_missing' => false,
                    'notes' => $akad['notes'] ?? null,
                    'provenance' => $akad,
                ],
            ];
        }

        // 11. BAST Records (Multi-BAST Rule 12)
        $bastRows = $this->normalizeHistoryRows($history['bast'] ?? [], 'bast');
        if (count($bastRows) > 1) {
            $fingerprints = array_map(fn ($r) => hash('sha256', json_encode([$r['document_number'] ?? '', $r['date_normalized'] ?? '', $r['status'] ?? ''], JSON_THROW_ON_ERROR)), $bastRows);
            if (count(array_unique($fingerprints)) > 1) {
                throw ValidationException::withMessages(['bast' => "Multi-BAST pada kandidat {$candidate->source_candidate_key} bertentangan/reissue. Memerlukan penanganan manual."]);
            }
            $bastRows = [$bastRows[0]];
            $bastRows[0]['provenance_all_rows'] = array_column($history['bast'], 'source_row');
        }

        foreach ($bastRows as $idx => $bast) {
            $bastPlanKey = $salesCasePlanKey.'_bast_'.($bast['source_row'] ?? $idx);
            $bastDate = $bast['date_normalized'] ?? null;
            if ($bastDate === null) {
                throw ValidationException::withMessages(['bast' => "BAST pada row {$bast['source_row']} kandidat {$candidate->source_candidate_key} tidak memiliki tanggal valid."]);
            }

            $ops[] = [
                'type' => LegacyMigrationPlanOperationType::CreateBast,
                'payload' => [
                    'plan_key' => $bastPlanKey,
                    'sales_case_plan_key' => $salesCasePlanKey,
                    'akad_plan_key' => $lastAkadPlanKey,
                    'source_sheet' => 'bast',
                    'source_row' => $bast['source_row'] ?? null,
                    'bast_number' => $bast['document_number'] ?? null,
                    'bast_date' => $bastDate,
                    'status' => $bast['status'] ?? 'COMPLETED',
                    'legacy_date_missing' => false,
                    'notes' => $bast['notes'] ?? null,
                    'provenance' => $bast,
                ],
            ];
        }

        // 12. Final Lifecycle & Unit state
        $ops[] = [
            'type' => LegacyMigrationPlanOperationType::SetFinalLifecycle,
            'payload' => [
                'sales_case_plan_key' => $salesCasePlanKey,
                'lifecycle_status' => $case['lifecycle_status'] ?? 'ACTIVE',
            ],
        ];

        $ops[] = [
            'type' => LegacyMigrationPlanOperationType::SetFinalUnitState,
            'payload' => [
                'unit_plan_key' => $unitPlanKey,
                'sales_case_plan_key' => $salesCasePlanKey,
                'state' => $case['lifecycle_status'] ?? 'ACTIVE',
            ],
        ];

        return $ops;
    }

    /** @return array<string, string> */
    private function emptyStringMap(): array
    {
        return [];
    }

    /** @param  array<int, mixed>  $rows
     * @return array<int, array<string, mixed>>
     */
    private function normalizeHistoryRows(array $rows, string $sheet): array
    {
        return array_map(function ($item) use ($sheet): array {
            if (is_array($item)) {
                return $item;
            }

            return [
                'source_sheet' => $sheet,
                'source_row' => (int) $item,
                'date_normalized' => null,
            ];
        }, $rows);
    }

    private function resolveBankForCandidate(LegacyMigrationCandidate $candidate, ?string $bankName, string $sheet, ?int $sourceRow): ?Bank
    {
        $direct = $this->resolveBank($bankName);
        if ($direct !== null || $bankName === null) {
            return $direct;
        }

        $mapped = $candidate->resolutions()
            ->where('resolution_type', LegacyResolutionType::MapBank->value)
            ->get()
            ->filter(fn ($resolution): bool => isset($resolution->selected_value['bank_id']))
            ->first(fn ($resolution): bool => ($resolution->selected_value['source_sheet'] ?? null) === $sheet
                && ($resolution->selected_value['source_row'] ?? null) === $sourceRow);

        $mapped ??= $candidate->resolutions()
            ->where('resolution_type', LegacyResolutionType::MapBank->value)
            ->get()
            ->filter(fn ($resolution): bool => isset($resolution->selected_value['bank_id']))
            ->first();

        if ($mapped === null) {
            return null;
        }

        return Bank::whereKey($mapped->selected_value['bank_id'])->first();
    }

    private function resolveBank(?string $bankName): ?Bank
    {
        $normalized = $bankName === null ? null : mb_strtolower(trim($bankName));
        if ($normalized === null || $normalized === '') {
            return null;
        }

        return Bank::whereRaw('LOWER(name) = ?', [$normalized])
            ->orWhereRaw('LOWER(code) = ?', [$normalized])
            ->first();
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
