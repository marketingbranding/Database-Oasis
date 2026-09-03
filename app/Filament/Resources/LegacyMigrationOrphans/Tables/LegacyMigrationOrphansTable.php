<?php

namespace App\Filament\Resources\LegacyMigrationOrphans\Tables;

use App\Enums\LegacyOrphanStatus;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

class LegacyMigrationOrphansTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('source_sheet')->label('Sheet'),
                TextColumn::make('source_row')->label('Row'),
                TextColumn::make('orphan_code')->label('Code')->badge(),
                TextColumn::make('severity')->label('Severity')->badge(),
                TextColumn::make('status')->label('Status')->badge(),
                TextColumn::make('created_at')->label('Created')->dateTime(),
            ])
            ->filters([
                SelectFilter::make('severity')->options(['REVIEW' => 'REVIEW', 'BLOCKING' => 'BLOCKING']),
                SelectFilter::make('status')->options(LegacyOrphanStatus::class),
            ])
            ->defaultSort('source_sheet');
    }
}
