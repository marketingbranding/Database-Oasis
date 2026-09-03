<?php

namespace App\Services;

use App\BankResponseType;
use App\DeveloperPpjbStatus;
use App\DocumentSubmissionType;
use App\Models\BankProcess;
use App\Models\BiCheck;
use App\Models\CaseNote;
use App\Models\DeveloperPpjb;
use App\Models\DocumentSubmission;
use App\Models\Psjb;
use App\Models\SalesCase;
use App\PsjbStatus;
use Illuminate\Support\Collection;

/**
 * Aggregates existing transactional records of one Sales Case into a
 * chronological, read-only timeline for human reading: OLDEST first,
 * newest at the bottom. No timeline table is written; this is a
 * presentation service only. Ordering: business dates first, ULID
 * (creation order) as deterministic tie-breaker.
 */
class SalesCaseTimelineService
{
    /** @return Collection<int, TimelineItem> */
    public function forCase(SalesCase $case): Collection
    {
        $case->loadMissing([
            'biChecks', 'psjbs', 'documentSubmissions.bank', 'bankProcesses.bank', 'bankProcesses.documentSubmission',
            'developerPpjbs', 'akad', 'bast', 'caseNotes.createdBy', 'createdBy',
            'previousCase', 'successorCase',
        ]);

        $items = collect()
            ->merge($this->caseCreatedItems($case))
            ->merge($this->biCheckItems($case))
            ->merge($this->psjbItems($case))
            ->merge($this->submissionItems($case))
            ->merge($this->bankProcessItems($case))
            ->merge($this->developerPpjbItems($case))
            ->merge($this->akadItems($case))
            ->merge($this->bastItems($case))
            ->merge($this->noteItems($case))
            ->merge($this->closureItems($case));

        return $items
            ->sortBy(fn (TimelineItem $item): array => [$item->date->getTimestamp(), $item->sourceId])
            ->values();
    }

    /** @return array<int, TimelineItem> */
    private function caseCreatedItems(SalesCase $case): array
    {
        $date = $case->booking_date ?? $case->created_at;
        $lines = [];

        if ($case->previousCase !== null) {
            $lines[] = sprintf(
                'Pindahan dari unit %s (%s).',
                $case->previousCase->unit->unit_code,
                $case->previousCase->case_status->getLabel(),
            );
        }

        return [new TimelineItem(
            date: $date,
            title: 'Sales Case dibuat',
            descriptionLines: $lines,
            actor: $case->createdBy !== null ? $case->createdBy->name : null,
            sourceType: 'sales_case',
            sourceId: $case->id,
        )];
    }

    /** @return array<int, TimelineItem> */
    private function biCheckItems(SalesCase $case): array
    {
        return $case->biChecks
            ->map(fn (BiCheck $bi): TimelineItem => new TimelineItem(
                date: $bi->check_date,
                title: 'BI Checking',
                descriptionLines: array_filter([$bi->description]),
                status: $bi->result->getLabel(),
                tone: $bi->result->value === 'CLEAR' ? 'success' : 'danger',
                actor: $bi->createdBy !== null ? $bi->createdBy->name : null,
                sourceType: 'bi_check',
                sourceId: $bi->id,
            ))
            ->all();
    }

    /** @return array<int, TimelineItem> */
    private function psjbItems(SalesCase $case): array
    {
        return $case->psjbs
            ->map(fn (Psjb $psjb): TimelineItem => new TimelineItem(
                date: $psjb->psjb_date,
                title: match ($psjb->status) {
                    PsjbStatus::Cancelled => 'PSJB dibatalkan',
                    PsjbStatus::Superseded => 'PSJB diterbitkan ulang',
                    PsjbStatus::Active => 'PSJB dibuat',
                },
                descriptionLines: array_filter([
                    $psjb->document_number !== null ? 'Nomor: '.$psjb->document_number : null,
                    $psjb->coordinator !== null ? 'Koordinator: '.$psjb->coordinator->name : null,
                ]),
                status: $psjb->status->getLabel(),
                tone: match ($psjb->status) {
                    PsjbStatus::Cancelled => 'danger',
                    PsjbStatus::Superseded => 'neutral',
                    PsjbStatus::Active => 'primary',
                },
                actor: $psjb->createdBy !== null ? $psjb->createdBy->name : null,
                sourceType: 'psjb',
                sourceId: $psjb->id,
            ))
            ->all();
    }

    /** @return array<int, TimelineItem> */
    private function submissionItems(SalesCase $case): array
    {
        return $case->documentSubmissions
            ->map(fn (DocumentSubmission $submission): TimelineItem => $submission->type === DocumentSubmissionType::CashInternal
                ? new TimelineItem(
                    date: $submission->submission_date,
                    title: 'Pemberkasan CASH selesai',
                    descriptionLines: array_filter([$submission->notes]),
                    status: 'SELESAI',
                    tone: 'success',
                    actor: $submission->createdBy !== null ? $submission->createdBy->name : null,
                    sourceType: 'document_submission',
                    sourceId: $submission->id,
                )
                : new TimelineItem(
                    date: $submission->submission_date,
                    title: sprintf('Pemberkasan #%d — %s', $submission->sequence, $submission->bank->name),
                    descriptionLines: array_filter([
                        $submission->bank_branch !== null ? 'Cabang bank: '.$submission->bank_branch : null,
                        $submission->notes,
                    ]),
                    status: $submission->status->getLabel(),
                    tone: 'primary',
                    groupLabel: sprintf('Pemberkasan #%d — %s', $submission->sequence, $submission->bank->name),
                    actor: $submission->createdBy !== null ? $submission->createdBy->name : null,
                    sourceType: 'document_submission',
                    sourceId: $submission->id,
                ))
            ->all();
    }

    /** @return array<int, TimelineItem> */
    private function bankProcessItems(SalesCase $case): array
    {
        return $case->bankProcesses
            ->map(fn (BankProcess $process): TimelineItem => new TimelineItem(
                date: $process->response_date,
                title: sprintf('Response Bank — %s', $process->bank->name),
                descriptionLines: array_filter([
                    $process->response_type === BankResponseType::Approved && $process->sp3k_number !== null
                        ? sprintf('SP3K: %s (%s)', $process->sp3k_number, $process->sp3k_date?->format('d M Y') ?? '-')
                        : null,
                    $process->notes,
                ]),
                status: $process->response_type->getLabel(),
                tone: match ($process->response_type) {
                    BankResponseType::Approved => 'success',
                    BankResponseType::Rejected => 'danger',
                    BankResponseType::Process, BankResponseType::Revision => 'warning',
                },
                groupLabel: $process->documentSubmission !== null
                    ? sprintf('Pemberkasan #%d — %s', $process->documentSubmission->sequence, $process->bank->name)
                    : null,
                actor: $process->createdBy !== null ? $process->createdBy->name : null,
                sourceType: 'bank_process',
                sourceId: $process->id,
            ))
            ->all();
    }

    /** @return array<int, TimelineItem> */
    private function developerPpjbItems(SalesCase $case): array
    {
        return $case->developerPpjbs
            ->map(fn (DeveloperPpjb $ppjb): TimelineItem => new TimelineItem(
                date: $ppjb->document_date,
                title: match ($ppjb->status) {
                    DeveloperPpjbStatus::Cancelled => 'PPJB Developer dibatalkan',
                    DeveloperPpjbStatus::Superseded => 'PPJB Developer diterbitkan ulang',
                    DeveloperPpjbStatus::Active => 'PPJB Developer dibuat',
                },
                descriptionLines: array_filter([
                    $ppjb->document_number !== null ? 'Nomor: '.$ppjb->document_number : null,
                    $ppjb->bank_process_id !== null && $ppjb->bankProcess->sp3k_number !== null
                        ? 'SP3K: '.$ppjb->bankProcess->sp3k_number
                        : null,
                    $ppjb->notes,
                ]),
                status: $ppjb->status->getLabel(),
                tone: match ($ppjb->status) {
                    DeveloperPpjbStatus::Cancelled => 'danger',
                    DeveloperPpjbStatus::Superseded => 'neutral',
                    DeveloperPpjbStatus::Active => 'primary',
                },
                actor: $ppjb->createdBy !== null ? $ppjb->createdBy->name : null,
                sourceType: 'developer_ppjb',
                sourceId: $ppjb->id,
            ))
            ->all();
    }

    /** @return array<int, TimelineItem> */
    private function akadItems(SalesCase $case): array
    {
        $akad = $case->akad;

        if ($akad === null) {
            return [];
        }

        return [new TimelineItem(
            date: $akad->akad_date,
            title: 'Akad',
            descriptionLines: array_filter([
                $akad->document_number !== null ? 'Nomor: '.$akad->document_number : null,
                $akad->akad_quality !== null ? 'Kualitas: '.$akad->akad_quality : null,
                $akad->developerPpjb !== null && $akad->developerPpjb->document_number !== null
                    ? 'PPJB: '.$akad->developerPpjb->document_number
                    : null,
                'Unit menjadi TERJUAL.',
            ]),
            actor: $akad->createdBy?->name,
            sourceType: 'akad_record',
            sourceId: $akad->id,
        )];
    }

    /** @return array<int, TimelineItem> */
    private function bastItems(SalesCase $case): array
    {
        $bast = $case->bast;

        if ($bast === null) {
            return [];
        }

        return [new TimelineItem(
            date: $bast->bast_date,
            title: 'BAST',
            descriptionLines: array_filter([
                $bast->bast_number !== null ? 'Nomor: '.$bast->bast_number : null,
                'Sales Case selesai.',
            ]),
            status: $bast->status->getLabel(),
            tone: $bast->status->value === 'COMPLETED' ? 'success' : 'danger',
            actor: $bast->createdBy?->name,
            sourceType: 'bast_record',
            sourceId: $bast->id,
        )];
    }

    /** @return array<int, TimelineItem> */
    private function noteItems(SalesCase $case): array
    {
        return $case->caseNotes
            ->map(fn (CaseNote $note): TimelineItem => new TimelineItem(
                date: $note->created_at,
                title: 'Catatan',
                descriptionLines: [$note->note],
                tone: 'neutral',
                actor: $note->createdBy?->name,
                sourceType: 'case_note',
                sourceId: $note->id,
            ))
            ->all();
    }

    /** @return array<int, TimelineItem> */
    private function closureItems(SalesCase $case): array
    {
        if ($case->closed_at === null) {
            return [];
        }

        $lines = array_filter([
            $case->closed_reason,
            $case->successorCase !== null
                ? sprintf('Pindah kavling ke unit %s.', $case->successorCase->unit->unit_code)
                : null,
        ]);

        return [new TimelineItem(
            date: $case->closed_at,
            title: 'Sales Case ditutup',
            descriptionLines: $lines,
            status: $case->case_status->getLabel(),
            tone: $case->case_status->value === 'COMPLETED' ? 'success' : 'neutral',
            sourceType: 'sales_case',
            sourceId: $case->id.'_closed',
        )];
    }
}
