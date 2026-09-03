<x-filament-panels::page>
    <x-filament::section>
        <div class="grid gap-4 md:grid-cols-3">
            <x-filament::input.wrapper>
                <x-filament::input type="month" wire:model.live="month" />
            </x-filament::input.wrapper>
            <x-filament::input.wrapper>
                <x-filament::input.select wire:model.live="branchId" :disabled="auth()->user()->isBranchScoped()">
                    @unless(auth()->user()->isBranchScoped())
                        <option value="">Semua Cabang</option>
                    @endunless
                    @foreach ($branches as $id => $name)
                        <option value="{{ $id }}">{{ $name }}</option>
                    @endforeach
                </x-filament::input.select>
            </x-filament::input.wrapper>
            <x-filament::input.wrapper>
                <x-filament::input.select wire:model.live="projectId">
                    <option value="">Semua Proyek</option>
                    @foreach ($projects as $id => $name)
                        <option value="{{ $id }}">{{ $name }}</option>
                    @endforeach
                </x-filament::input.select>
            </x-filament::input.wrapper>
        </div>
    </x-filament::section>

    <div class="grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
        @foreach ([
            ['Target Akad', $metrics['target'] ?? 'Target belum diisi'],
            ['Realisasi Akad', $metrics['akad']],
            ['Achievement', $metrics['achievement'] === null ? '-' : $metrics['achievement'].'%'],
            ['BAST Bulan Ini', $metrics['bast']],
            ['SP3K Stock', $metrics['sp3k_stock']],
            ['SP3K Units with Kendala', $metrics['sp3k_with_issues']],
            ['Total Open Kendala', $metrics['open_issues']],
            ['Readiness Data Incomplete', $metrics['readiness_incomplete']],
        ] as [$label, $value])
            <x-filament::section compact>
                <p class="text-sm text-gray-500 dark:text-gray-400">{{ $label }}</p>
                <p class="text-2xl font-semibold text-gray-950 dark:text-white">{{ $value }}</p>
            </x-filament::section>
        @endforeach
    </div>

    <div class="grid gap-6 xl:grid-cols-2">
        <x-filament::section heading="Realisasi Akad M1–M4" description="M1 1–7, M2 8–14, M3 15–21, M4 22–akhir bulan">
            <div class="grid grid-cols-2 gap-4 sm:grid-cols-4">
                @foreach ($metrics['weekly'] as $week => $count)
                    <div class="rounded-lg bg-gray-50 p-4 text-center dark:bg-white/5">
                        <p class="text-sm text-gray-500 dark:text-gray-400">{{ $week }}</p>
                        <p class="text-2xl font-semibold text-gray-950 dark:text-white">{{ $count }}</p>
                    </div>
                @endforeach
            </div>
            <x-slot name="footer">
                <x-filament::link :href="App\Filament\Pages\AkadMonitoring::getUrl(['month' => $month, 'branch' => $branchId, 'project' => $projectId])">Lihat detail Akad</x-filament::link>
            </x-slot>
        </x-filament::section>

        <x-filament::section heading="SP3K Aging">
            <div class="grid grid-cols-2 gap-4 sm:grid-cols-4">
                @foreach ($metrics['sp3k_aging'] as $bucket => $count)
                    <a class="rounded-lg bg-gray-50 p-4 text-center hover:bg-gray-100 dark:bg-white/5 dark:hover:bg-white/10" href="{{ App\Filament\Pages\Sp3kMonitoring::getUrl(['aging' => $bucket, 'branch' => $branchId, 'project' => $projectId]) }}">
                        <p class="text-sm text-gray-500 dark:text-gray-400">{{ $agingLabels[$bucket] }}</p>
                        <p class="text-2xl font-semibold text-gray-950 dark:text-white">{{ $count }}</p>
                    </a>
                @endforeach
            </div>
        </x-filament::section>
    </div>

    <x-filament::section heading="Kendala SP3K" description="Satu unit dapat memiliki lebih dari satu kategori kendala.">
        <div class="grid grid-cols-2 gap-4 sm:grid-cols-4">
            @foreach ($metrics['issue_breakdown'] as $category => $count)
                <a class="rounded-lg bg-gray-50 p-4 text-center hover:bg-gray-100 dark:bg-white/5 dark:hover:bg-white/10" href="{{ App\Filament\Pages\Sp3kMonitoring::getUrl(['issue' => $category, 'branch' => $branchId, 'project' => $projectId]) }}">
                    <p class="text-sm text-gray-500 dark:text-gray-400">{{ $issueLabels[$category] }}</p>
                    <p class="text-2xl font-semibold text-gray-950 dark:text-white">{{ $count }}</p>
                </a>
            @endforeach
        </div>
    </x-filament::section>
</x-filament-panels::page>
