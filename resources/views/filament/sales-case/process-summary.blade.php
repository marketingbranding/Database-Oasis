@php
    /** @var App\Models\SalesCase $case */
    $submission = $case->latestSubmission;
    $latestResponse = $case->latestBankProcess;
    $approval = $case->currentApprovedBankProcess;
    $isCash = $case->financing_type->value === 'CASH';
    $days = $case->daysInCurrentStage();
@endphp
<div class="grid gap-2 text-sm sm:grid-cols-2 lg:grid-cols-5">
    <div>
        <p class="text-xs text-gray-500 dark:text-gray-400">Stage Saat Ini</p>
        <p class="font-semibold">{{ $case->current_stage->getLabel() }}</p>
    </div>
    <div>
        <p class="text-xs text-gray-500 dark:text-gray-400">Bank Saat Ini</p>
        <p class="font-semibold">{{ $isCash ? '-' : ($submission?->bank?->name ?? '-') }}</p>
    </div>
    <div>
        <p class="text-xs text-gray-500 dark:text-gray-400">Response Terakhir</p>
        <p class="font-semibold">{{ $isCash ? '-' : ($latestResponse?->response_type->getLabel() ?? '-') }}</p>
    </div>
    <div>
        <p class="text-xs text-gray-500 dark:text-gray-400">SP3K</p>
        <p class="font-semibold">{{ $isCash ? '-' : ($approval?->sp3k_number ?? '-') }}</p>
    </div>
    <div>
        <p class="text-xs text-gray-500 dark:text-gray-400">Hari di Stage Ini</p>
        <p class="font-semibold">{{ $days === null ? '-' : $days.' hari' }}</p>
    </div>
</div>
