@php
    /** @var App\Models\SalesCase $case */
    /** @var array<string, 'done'|'current'|'upcoming'> $progress */
    use App\SalesCaseStage;

    $progress = $case->stageProgress();
    $labels = [
        'done' => 'Selesai',
        'current' => 'Saat ini',
        'upcoming' => 'Berikutnya',
    ];
@endphp
<ol class="grid grid-cols-2 gap-3 md:grid-cols-3 xl:grid-cols-4" role="list" aria-label="Alur proses sales case">
    @foreach ($progress as $stageValue => $state)
        @php($stage = SalesCaseStage::from($stageValue))
        <li
            @class([
                'relative min-h-24 rounded-xl border p-3 transition-colors',
                'border-success-200 bg-success-50/70 dark:border-success-500/30 dark:bg-success-500/10' => $state === 'done',
                'border-primary-400 bg-primary-50 ring-2 ring-primary-300 dark:border-primary-400 dark:bg-primary-500/10 dark:ring-primary-500/40' => $state === 'current',
                'border-gray-200 bg-gray-50/60 dark:border-white/10 dark:bg-white/5' => $state === 'upcoming',
            ])
            aria-current="{{ $state === 'current' ? 'step' : 'false' }}"
        >
            <div class="flex items-start justify-between gap-2">
                <span @class([
                    'inline-flex h-7 w-7 shrink-0 items-center justify-center rounded-full text-xs font-bold',
                    'bg-success-500 text-white' => $state === 'done',
                    'bg-primary-500 text-white' => $state === 'current',
                    'bg-gray-200 text-gray-500 dark:bg-gray-700 dark:text-gray-300' => $state === 'upcoming',
                ])>
                    @if ($state === 'done')
                        <x-filament::icon icon="heroicon-m-check" class="h-4 w-4" />
                    @else
                        {{ $loop->iteration }}
                    @endif
                </span>
                <span @class([
                    'rounded-md px-2 py-0.5 text-[11px] font-semibold uppercase tracking-wide',
                    'bg-success-100 text-success-700 dark:bg-success-500/20 dark:text-success-400' => $state === 'done',
                    'bg-primary-100 text-primary-700 dark:bg-primary-500/20 dark:text-primary-300' => $state === 'current',
                    'bg-gray-100 text-gray-500 dark:bg-white/5 dark:text-gray-400' => $state === 'upcoming',
                ])>{{ $labels[$state] }}</span>
            </div>
            <p @class([
                'mt-3 text-sm font-semibold leading-snug',
                'text-success-800 dark:text-success-300' => $state === 'done',
                'text-primary-800 dark:text-primary-200' => $state === 'current',
                'text-gray-600 dark:text-gray-300' => $state === 'upcoming',
            ])>{{ $stage->getLabel() }}</p>
        </li>
    @endforeach
</ol>
