<x-filament-panels::page>
    <x-filament::section>
        <x-filament::input.wrapper>
            <x-filament::input type="month" wire:model.live="month" />
        </x-filament::input.wrapper>
    </x-filament::section>

    {{ $this->table }}
</x-filament-panels::page>
