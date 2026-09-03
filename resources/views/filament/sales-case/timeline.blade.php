@php
    /** @var Illuminate\Support\Collection<int, App\Services\TimelineItem> $items */

    $tones = [
        'success' => 'bg-success-500 ring-success-200 dark:ring-success-500/30',
        'danger' => 'bg-danger-500 ring-danger-200 dark:ring-danger-500/30',
        'warning' => 'bg-warning-500 ring-warning-200 dark:ring-warning-500/30',
        'neutral' => 'bg-gray-400 ring-gray-200 dark:ring-gray-500/30',
        'primary' => 'bg-primary-500 ring-primary-200 dark:ring-primary-500/30',
    ];
    $badges = [
        'success' => 'bg-success-100 text-success-700 dark:bg-success-500/10 dark:text-success-400',
        'danger' => 'bg-danger-100 text-danger-700 dark:bg-danger-500/10 dark:text-danger-400',
        'warning' => 'bg-warning-100 text-warning-700 dark:bg-warning-500/10 dark:text-warning-400',
        'neutral' => 'bg-gray-100 text-gray-600 dark:bg-white/5 dark:text-gray-300',
        'primary' => 'bg-primary-100 text-primary-700 dark:bg-primary-500/10 dark:text-primary-400',
    ];
@endphp
<ol class="space-y-6" role="list" aria-label="Riwayat proses sales case, dari awal hingga terbaru">
    @forelse ($items as $item)
        <li class="relative flex gap-4">
            <div class="flex flex-col items-center">
                <span @class(['mt-1 h-3.5 w-3.5 shrink-0 rounded-full ring-4', $tones[$item->tone] ?? $tones['primary']]) aria-hidden="true"></span>
                <span class="mt-1 w-px flex-1 bg-gray-200 dark:bg-white/10" aria-hidden="true"></span>
            </div>
            <div class="flex-1 pb-1">
                <div class="flex flex-wrap items-center gap-2">
                    <span class="inline-flex items-center rounded-md bg-gray-100 px-2 py-0.5 text-xs font-semibold text-gray-500 tabular-nums dark:bg-white/5 dark:text-gray-400">
                        {{ $item->date->format('d M Y') }}
                    </span>
                    <span class="font-semibold text-gray-950 dark:text-white">{{ $item->title }}</span>
                    @if ($item->status !== null)
                        <span @class(['rounded-md px-2 py-0.5 text-xs font-semibold', $badges[$item->tone] ?? $badges['primary']])>{{ $item->status }}</span>
                    @endif
                </div>
                @if ($item->groupLabel !== null && $item->groupLabel !== $item->title)
                    <p class="mt-0.5 text-xs text-gray-400 dark:text-gray-500">
                        <x-filament::icon icon="heroicon-m-arrow-uturn-down" class="mr-0.5 inline h-3 w-3" />
                        {{ $item->groupLabel }}
                    </p>
                @endif
                @if ($item->descriptionLines !== [])
                    <div class="mt-1 space-y-0.5 text-sm text-gray-600 dark:text-gray-300">
                        @foreach ($item->descriptionLines as $line)
                            <p>{{ $line }}</p>
                        @endforeach
                    </div>
                @endif
                @if ($item->actor !== null)
                    <p class="mt-1 text-xs text-gray-400 dark:text-gray-500">Dicatat oleh: {{ $item->actor }}</p>
                @endif
            </div>
        </li>
    @empty
        <li class="text-sm text-gray-500 dark:text-gray-400">Belum ada riwayat.</li>
    @endforelse
</ol>
