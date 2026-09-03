@php
    /** @var App\Models\SalesCase $case */
    use App\SalesCaseStatus;

    $isCash = $case->financing_type->value === 'CASH';
    $isCompleted = $case->case_status === SalesCaseStatus::Completed;
    $days = $case->daysInCurrentStage();
@endphp
@if ($isCompleted)
    <div class="grid gap-3 sm:grid-cols-2 lg:grid-cols-4">
        <div class="rounded-xl border border-success-200 bg-success-50 p-4 dark:border-success-500/30 dark:bg-success-500/10">
            <p class="text-xs text-success-600 dark:text-success-400">Status</p>
            <p class="text-lg font-semibold text-success-700 dark:text-success-300">Completed</p>
        </div>
        <div class="rounded-xl border border-gray-200 bg-white p-4 dark:border-white/10 dark:bg-white/5">
            <p class="text-xs text-gray-500 dark:text-gray-400">Tanggal Akad</p>
            <p class="text-lg font-semibold">{{ $case->akad?->akad_date?->format('d M Y') ?? '-' }}</p>
        </div>
        <div class="rounded-xl border border-gray-200 bg-white p-4 dark:border-white/10 dark:bg-white/5">
            <p class="text-xs text-gray-500 dark:text-gray-400">Tanggal BAST</p>
            <p class="text-lg font-semibold">{{ $case->bast?->bast_date?->format('d M Y') ?? '-' }}</p>
        </div>
        <div class="rounded-xl border border-gray-200 bg-white p-4 dark:border-white/10 dark:bg-white/5">
            <p class="text-xs text-gray-500 dark:text-gray-400">Unit</p>
            <p class="text-lg font-semibold">{{ $case->unit->unit_code }}</p>
            <p class="text-xs text-gray-500 dark:text-gray-400">{{ $case->financing_type->getLabel() }}</p>
        </div>
    </div>
@else
    <div class="grid gap-3 sm:grid-cols-3">
        <div class="rounded-xl border border-primary-200 bg-primary-50 p-4 dark:border-primary-500/30 dark:bg-primary-500/10">
            <p class="text-xs text-primary-600 dark:text-primary-400">Stage Saat Ini</p>
            <p class="text-lg font-semibold text-primary-700 dark:text-primary-300">{{ $case->current_stage->getLabel() }}</p>
            <p class="text-xs text-primary-500 dark:text-primary-400">{{ $days === null ? 'Baru dibuat' : $days.' hari di stage ini' }}</p>
        </div>
        <div class="rounded-xl border border-gray-200 bg-white p-4 dark:border-white/10 dark:bg-white/5">
            <p class="text-xs text-gray-500 dark:text-gray-400">Pembiayaan</p>
            <p class="text-lg font-semibold">{{ $case->financing_type->getLabel() }}</p>
            <p class="text-xs text-gray-500 dark:text-gray-400">Unit {{ $case->unit->unit_code }}</p>
        </div>
        @if ($isCash)
            @php($cashPemberkasan = $case->documentSubmissions()->where('type', 'CASH_INTERNAL')->first())
            <div class="rounded-xl border border-gray-200 bg-white p-4 dark:border-white/10 dark:bg-white/5">
                <p class="text-xs text-gray-500 dark:text-gray-400">Pemberkasan CASH</p>
                <p class="text-lg font-semibold">{{ $cashPemberkasan !== null ? 'Selesai' : 'Belum' }}</p>
                <p class="text-xs text-gray-500 dark:text-gray-400">{{ $cashPemberkasan?->submission_date?->format('d M Y') ?? 'Menunggu PSJB aktif' }}</p>
            </div>
        @else
            @php($approval = $case->currentApprovedBankProcess)
            <div class="rounded-xl border border-gray-200 bg-white p-4 dark:border-white/10 dark:bg-white/5">
                <p class="text-xs text-gray-500 dark:text-gray-400">Bank</p>
                <p class="text-lg font-semibold">{{ $case->latestSubmission?->bank?->name ?? 'Belum ada' }}</p>
                @if ($approval !== null)
                    <p class="text-xs text-success-600 dark:text-success-400">SP3K: {{ $approval->sp3k_number }}</p>
                @else
                    <p class="text-xs text-gray-500 dark:text-gray-400">Response: {{ $case->latestBankProcess?->response_type->getLabel() ?? 'Belum ada' }}</p>
                @endif
            </div>
        @endif
    </div>
@endif
