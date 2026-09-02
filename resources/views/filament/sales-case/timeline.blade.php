@php
    /** @var Illuminate\Support\Collection<int, App\Services\TimelineItem> $items */
@endphp
<ol class="relative space-y-4 border-l border-gray-200 pl-5 dark:border-white/10">
    @forelse ($items as $item)
        <li class="relative">
            <span
                @class([
                    'absolute -left-[27px] top-1.5 h-3 w-3 rounded-full border-2',
                    'border-primary-500 bg-primary-500' => $item->status !== 'NOTE' && $item->status !== null,
                    'border-gray-300 bg-gray-100 dark:border-gray-600 dark:bg-gray-700' => $item->status === null || $item->status === 'NOTE',
                ])
            ></span>
            <div class="flex flex-wrap items-center gap-2">
                <span class="text-xs font-medium text-gray-500 dark:text-gray-400">{{ $item->date->format('d M Y') }}</span>
                <span class="font-semibold">{{ $item->title }}</span>
                @if ($item->status !== null)
                    <span
                        @class([
                            'rounded-md px-1.5 py-0.5 text-xs font-medium',
                            'bg-gray-100 text-gray-600 dark:bg-white/5 dark:text-gray-300' => $item->status === 'NOTE',
                            'bg-primary-100 text-primary-700 dark:bg-primary-500/10 dark:text-primary-400' => $item->status !== 'NOTE',
                        ])
                    >{{ $item->status }}</span>
                @endif
            </div>
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
        </li>
    @empty
        <li class="text-sm text-gray-500 dark:text-gray-400">Belum ada aktivitas.</li>
    @endforelse
</ol>
