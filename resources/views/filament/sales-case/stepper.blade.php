@php
    /** @var App\Models\SalesCase $case */
    /** @var array<string, 'done'|'current'|'upcoming'> $progress */
    use App\SalesCaseStage;

    $progress = $case->stageProgress();
    $icons = ['done' => '✓', 'current' => '●', 'upcoming' => '○'];
    $stages = array_keys($progress);
@endphp
<div class="flex flex-wrap items-center gap-x-2 gap-y-3 text-sm">
    @foreach ($progress as $stageValue => $state)
        @php($stage = SalesCaseStage::from($stageValue))
        @php($isLast = $stageValue === $stages[count($stages) - 1])
        <span
            @class([
                'inline-flex items-center gap-1.5 rounded-md px-2.5 py-1 font-medium',
                'bg-success-100 text-success-700 dark:bg-success-500/10 dark:text-success-400' => $state === 'done',
                'bg-primary-100 text-primary-700 dark:bg-primary-500/10 dark:text-primary-400' => $state === 'current',
                'bg-gray-100 text-gray-500 dark:bg-white/5 dark:text-gray-400' => $state === 'upcoming',
            ])
        >
            <span>{{ $icons[$state] }}</span>
            <span>{{ $stage->getLabel() }}</span>
        </span>
        @unless ($isLast)
            <span class="text-gray-300 dark:text-gray-600">→</span>
        @endunless
    @endforeach
</div>
