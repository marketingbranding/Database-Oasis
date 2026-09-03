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

    $segments = [];
    $lastGroupIndex = null;
    foreach ($items as $item) {
        $isAttemptHeader = $item->sourceType === 'document_submission'
            && $item->groupLabel !== null
            && $item->groupLabel === $item->title;

        if ($item->sourceType === 'bank_process'
            && $item->groupLabel !== null
            && $lastGroupIndex !== null
            && $segments[$lastGroupIndex]['header']->groupLabel === $item->groupLabel) {
            $segments[$lastGroupIndex]['responses'][] = $item;
            continue;
        }

        if ($isAttemptHeader) {
            $segments[] = ['header' => $item, 'responses' => []];
            $lastGroupIndex = count($segments) - 1;
            continue;
        }

        $lastGroupIndex = null;
        $segments[] = ['item' => $item];
    }

    $renderItem = function (App\Services\TimelineItem $item, bool $nested = false) use ($tones, $badges): \Illuminate\Support\HtmlString {
        $dotTone = $tones[$item->tone] ?? $tones['primary'];
        $badgeTone = $badges[$item->tone] ?? $badges['primary'];
        $html = '<div class="' . ($nested ? 'flex gap-3 py-2 pl-3 border-l-2 border-gray-100 dark:border-white/10' : 'flex gap-4') . '">'
            . '<div class="flex flex-col items-center">'
            . '<span class="' . ($nested ? 'mt-1 h-2.5 w-2.5' : 'mt-1 h-3.5 w-3.5') . ' shrink-0 rounded-full ring-4 ' . $dotTone . '" aria-hidden="true"></span>'
            . ($nested ? '' : '<span class="mt-1 w-px flex-1 bg-gray-200 dark:bg-white/10" aria-hidden="true"></span>')
            . '</div>'
            . '<div class="flex-1 pb-1">'
            . '<div class="flex flex-wrap items-center gap-2">'
            . ($nested ? '' : '<span class="inline-flex items-center rounded-md bg-gray-100 px-2 py-0.5 text-xs font-semibold text-gray-500 tabular-nums dark:bg-white/5 dark:text-gray-400">' . $item->date->format('d M Y') . '</span>')
            . '<span class="' . ($nested ? 'text-sm' : '') . ' font-semibold text-gray-950 dark:text-white">' . e($item->title) . '</span>'
            . ($item->status !== null ? '<span class="rounded-md px-2 py-0.5 text-xs font-semibold ' . $badgeTone . '">' . e($item->status) . '</span>' : '')
            . '</div>'
            . ($item->descriptionLines !== []
                ? '<div class="mt-1 space-y-0.5 text-sm text-gray-600 dark:text-gray-300">' . collect($item->descriptionLines)->map(fn ($line) => '<p>' . e($line) . '</p>')->implode('') . '</div>'
                : '')
            . ($item->actor !== null && ! $nested ? '<p class="mt-1 text-xs text-gray-400 dark:text-gray-500">Dicatat oleh: ' . e($item->actor) . '</p>' : '')
            . '</div>'
            . '</div>';

        return new \Illuminate\Support\HtmlString($html);
    };
@endphp
<ol class="space-y-5" role="list" aria-label="Riwayat proses sales case, dari awal hingga terbaru">
    @forelse ($segments as $segment)
        @if (array_key_exists('header', $segment))
            @php($header = $segment['header'])
            <li class="rounded-xl border border-gray-200 bg-gray-50/60 p-3 dark:border-white/10 dark:bg-white/5">
                <div class="flex flex-wrap items-center gap-2">
                    <span class="inline-flex items-center rounded-md bg-gray-100 px-2 py-0.5 text-xs font-semibold text-gray-500 tabular-nums dark:bg-white/5 dark:text-gray-400">
                        {{ $header->date->format('d M Y') }}
                    </span>
                    <span class="font-semibold text-gray-950 dark:text-white">{{ $header->title }}</span>
                    @if ($header->status !== null)
                        <span @class(['rounded-md px-2 py-0.5 text-xs font-semibold', $badges[$header->tone] ?? $badges['primary']])>{{ $header->status }}</span>
                    @endif
                    @if ($header->actor !== null)
                        <span class="text-xs text-gray-400 dark:text-gray-500">Dicatat oleh: {{ $header->actor }}</span>
                    @endif
                </div>
                @if ($header->descriptionLines !== [])
                    <div class="mt-1 space-y-0.5 text-sm text-gray-600 dark:text-gray-300">
                        @foreach ($header->descriptionLines as $line)
                            <p>{{ $line }}</p>
                        @endforeach
                    </div>
                @endif
                @if ($segment['responses'] !== [])
                    <div class="mt-2 space-y-1">
                        @foreach ($segment['responses'] as $response)
                            {{ $renderItem($response, nested: true) }}
                        @endforeach
                    </div>
                @endif
            </li>
        @else
            <li>{{ $renderItem($segment['item']) }}</li>
        @endif
    @empty
        <li class="text-sm text-gray-500 dark:text-gray-400">Belum ada riwayat.</li>
    @endforelse
</ol>
