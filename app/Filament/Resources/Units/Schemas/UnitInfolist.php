<?php

namespace App\Filament\Resources\Units\Schemas;

use App\UnitStatus;
use App\UtilityStatus;
use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class UnitInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Unit / Kavling')
                    ->columns(3)
                    ->schema([
                        TextEntry::make('unit_code')->label('Kode Unit'),
                        TextEntry::make('project.name')->label('Proyek'),
                        TextEntry::make('status')->label('Status Operasional')
                            ->badge()
                            ->formatStateUsing(fn (UnitStatus $state): string => $state->getLabel()),
                        TextEntry::make('block')->label('Blok')->placeholder('-'),
                        TextEntry::make('number')->label('Nomor')->placeholder('-'),
                        TextEntry::make('building_progress')->label('Progres Bangunan')
                            ->formatStateUsing(fn ($state): string => $state === null ? '-' : $state.'%')
                            ->placeholder('-'),
                        TextEntry::make('electricity_status')->label('Listrik')->badge()
                            ->formatStateUsing(fn (?UtilityStatus $state): ?string => $state?->getLabel())
                            ->placeholder('-'),
                        TextEntry::make('water_status')->label('Air')->badge()
                            ->formatStateUsing(fn (?UtilityStatus $state): ?string => $state?->getLabel())
                            ->placeholder('-'),
                    ]),
            ]);
    }
}
