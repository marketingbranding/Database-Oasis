<?php

namespace App\Filament\Resources\LegacyMigrationBatches\Tables;

use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class LegacyMigrationBatchesTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('source_filename')->label('Source')->searchable(),
                TextColumn::make('source_fingerprint')->label('Fingerprint')->copyable()->limit(12),
                TextColumn::make('createdBy.name')->label('Dibuat Oleh'),
                TextColumn::make('status')->label('Status')->badge(),
                TextColumn::make('created_at')->label('Dibuat')->dateTime()->sortable(),
            ])
            ->defaultSort('created_at', 'desc');
    }
}
