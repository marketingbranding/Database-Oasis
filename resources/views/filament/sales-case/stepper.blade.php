@php
    /** @var App\Models\SalesCase $case */
    /** @var array<string, 'done'|'current'|'upcoming'> $progress */
    use App\SalesCaseStage;

    $progress = $case->stageProgress();
    $stages = array_keys($progress);
@endphp
<ol class="flex flex-wrap items-stretch gap-y-3 text-sm" role="list" aria-label="Alur proses sales case">
    @foreach ($stages as $index => $stageValue)
        @php($stage = SalesCaseStage::from($stageValue))
        @php($state = $progress[$stageValue])
        @php($isLast = $index === count($stages) - 1)
        <li class="flex items-center">
            <span
                @class([
                    'inline-flex items-center gap-2 rounded-lg border px-3 py-1.5 font-medium whitespace-nowrap',
                    'border-success-200 bg-success-50 text-success-700 dark:border-success-500/30 dark:bg-success-500/10 dark:text-success-400' => $state === 'done',
                    'border-primary-300 bg-primary-50 text-primary-700 ring-2 ring-primary-300 dark:border-primary-500/40 dark:bg-primary-500/10 dark:text-primary-300 dark:ring-primary-500/40' => $state === 'current',
                    'border-gray-200 bg-gray-50 text-gray-500 dark:border-white/10 dark:bg-white/5 dark:text-gray-400' => $state === 'upcoming',
                ])
            >
                @if ($state === 'done')
                    <x-filament::icon icon="heroicon-m-check-circle" class="h-4 w-4" />
                @elseif ($state === 'current')
                    <span class="relative flex h-2.5 w-2.5">
                        <span class="absolute inline-flex h-full w-full animate-ping rounded-full bg-primary-400 opacity-75"></span>
                        <span class="relative inline-flex h-2.5 w-2.5 rounded-full bg-primary-500"></span>
                    </span>
                @else
                    <span class="h-2.5 w-2.5 rounded-full border-2 border-current opacity-50"></span>
                @endif
                <span>{{ $stage->getLabel() }}</span>
            </span>
            @unless ($isLast)
                <span class="mx-1 h-px w-4 bg-gray-300 sm:w-6 dark:bg-gray-600" aria-hidden="true"></span>
            @endunless
        </li>
    @endforeach
</ol>
