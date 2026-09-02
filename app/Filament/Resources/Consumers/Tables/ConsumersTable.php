<?php

namespace App\Filament\Resources\Consumers\Tables;

use App\SalesCaseStage;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class ConsumersTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('name')
                    ->label('Nama')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('nik')
                    ->label('NIK')
                    ->searchable(),
                TextColumn::make('phone')
                    ->label('Telepon')
                    ->searchable(),
                TextColumn::make('activeSalesCase.unit.unit_code')
                    ->label('Unit Aktif')
                    ->placeholder('-'),
                TextColumn::make('activeSalesCase.current_stage')
                    ->label('Stage')
                    ->badge()
                    ->placeholder('-')
                    ->formatStateUsing(fn (?SalesCaseStage $state): ?string => $state?->getLabel()),
            ])
            ->recordActions([
                EditAction::make()
                    ->label('Ubah'),
            ])
            ->defaultSort('name');
    }
}
