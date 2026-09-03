@php
    /** @var App\Models\SalesCase $case */
    use App\SalesCaseStatus;

    $isCash = $case->financing_type->value === 'CASH';
    $isCompleted = $case->case_status === SalesCaseStatus::Completed;
    $days = $case->daysInCurrentStage();
@endphp
<div data-process-summary class="overflow-hidden rounded-2xl border border-gray-200 bg-white shadow-sm dark:border-white/10 dark:bg-gray-900">
    @if ($isCompleted)
        <div class="flex flex-wrap items-center gap-3 border-b border-success-100 bg-success-50 px-5 py-4 dark:border-success-500/20 dark:bg-success-500/10">
            <span class="inline-flex items-center gap-2 rounded-lg bg-success-500 px-3 py-1 text-sm font-semibold text-white">
                <x-filament::icon icon="heroicon-m-check-badge" class="h-4 w-4" />
                Completed
            </span>
            <div class="text-sm">
                <span class="font-semibold text-gray-950 dark:text-white">{{ $case->unit->unit_code }}</span>
                <span class="mx-1.5 text-gray-300 dark:text-gray-600">·</span>
                <span class="text-gray-600 dark:text-gray-300">{{ $case->financing_type->getLabel() }}</span>
                <span class="mx-1.5 text-gray-300 dark:text-gray-600">·</span>
                <span class="text-gray-600 dark:text-gray-300">{{ $case->branch->name }}</span>
            </div>
        </div>
        <div class="grid grid-cols-2 gap-x-6 gap-y-4 px-5 py-4 sm:grid-cols-4">
            <div>
                <p class="text-xs font-medium text-gray-400 dark:text-gray-500">Tanggal Akad</p>
                <p class="mt-0.5 text-base font-semibold text-gray-950 dark:text-white">{{ $case->akad?->akad_date?->format('d M Y') ?? '-' }}</p>
            </div>
            <div>
                <p class="text-xs font-medium text-gray-400 dark:text-gray-500">Tanggal BAST</p>
                <p class="mt-0.5 text-base font-semibold text-gray-950 dark:text-white">{{ $case->bast?->bast_date?->format('d M Y') ?? '-' }}</p>
            </div>
            <div>
                <p class="text-xs font-medium text-gray-400 dark:text-gray-500">Nomor Akad</p>
                <p class="mt-0.5 text-base font-semibold text-gray-950 dark:text-white">{{ $case->akad?->document_number ?? '-' }}</p>
            </div>
            <div>
                <p class="text-xs font-medium text-gray-400 dark:text-gray-500">Nomor BAST</p>
                <p class="mt-0.5 text-base font-semibold text-gray-950 dark:text-white">{{ $case->bast?->bast_number ?? '-' }}</p>
            </div>
        </div>
    @else
        <div class="flex flex-wrap items-center justify-between gap-3 border-b border-gray-100 bg-gray-50 px-5 py-4 dark:border-white/5 dark:bg-white/5">
            <div class="flex items-center gap-3">
                <span class="inline-flex items-center gap-2 rounded-lg bg-primary-500 px-3 py-1 text-sm font-semibold text-white">
                    <x-filament::icon icon="heroicon-m-flag" class="h-4 w-4" />
                    {{ $case->current_stage->getLabel() }}
                </span>
                <span class="text-sm text-gray-500 dark:text-gray-400">{{ $days === null ? 'Baru dibuat' : $days.' hari di stage ini' }}</span>
            </div>
            <span @class([
                'rounded-md px-2 py-1 text-xs font-semibold',
                'bg-warning-100 text-warning-700 dark:bg-warning-500/10 dark:text-warning-400' => $isCash,
                'bg-primary-100 text-primary-700 dark:bg-primary-500/10 dark:text-primary-400' => ! $isCash,
            ])>{{ $case->financing_type->getLabel() }}</span>
        </div>
        <div class="grid gap-4 px-5 py-4 sm:grid-cols-3">
            <div>
                <p class="text-xs font-medium text-gray-400 dark:text-gray-500">Unit / Kavling</p>
                <p class="mt-0.5 text-base font-semibold text-gray-950 dark:text-white">{{ $case->unit->unit_code }}</p>
            </div>
            @if ($isCash)
                @php($cashPemberkasan = $case->documentSubmissions()->where('type', 'CASH_INTERNAL')->first())
                <div>
                    <p class="text-xs font-medium text-gray-400 dark:text-gray-500">Progress CASH</p>
                    <p class="mt-0.5 text-base font-semibold text-gray-950 dark:text-white">
                        {{ $cashPemberkasan !== null ? 'Pemberkasan selesai' : 'Pemberkasan belum selesai' }}
                    </p>
                    <p class="text-xs text-gray-400 dark:text-gray-500">
                        {{ $cashPemberkasan !== null ? 'Selesai '.$cashPemberkasan->submission_date->format('d M Y') : 'Menunggu PSJB aktif' }}
                    </p>
                </div>
                <div>
                    <p class="text-xs font-medium text-gray-400 dark:text-gray-500">Tanpa Bank</p>
                    <p class="mt-0.5 text-sm text-gray-600 dark:text-gray-300">Alur CASH: PSJB → Pemberkasan → PPJB</p>
                </div>
            @else
                @php($approval = $case->currentApprovedBankProcess)
                <div>
                    <p class="text-xs font-medium text-gray-400 dark:text-gray-500">Bank saat ini</p>
                    <p class="mt-0.5 text-base font-semibold text-gray-950 dark:text-white">{{ $case->latestSubmission?->bank?->name ?? 'Belum ada' }}</p>
                </div>
                <div>
                    <p class="text-xs font-medium text-gray-400 dark:text-gray-500">Response terakhir</p>
                    <p class="mt-0.5 text-base font-semibold text-gray-950 dark:text-white">{{ $case->latestBankProcess?->response_type->getLabel() ?? 'Belum ada' }}</p>
                    @if ($approval !== null)
                        <p class="mt-0.5 inline-flex items-center gap-1 rounded-md bg-success-100 px-2 py-0.5 text-xs font-semibold text-success-700 dark:bg-success-500/10 dark:text-success-400">
                            <x-filament::icon icon="heroicon-m-check-circle" class="h-3.5 w-3.5" />
                            SP3K: {{ $approval->sp3k_number }}
                        </p>
                    @endif
                </div>
            @endif
        </div>
    @endif
</div>
