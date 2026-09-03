<?php

namespace App\Filament\Resources\LegacyMigrationCandidates\Tables;

use App\MigrationReadiness;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

class LegacyMigrationCandidatesTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('batch.source_filename')->label('Batch'),
                TextColumn::make('proposed_sales_case.name_normalized')->label('Konsumen'),
                TextColumn::make('proposed_unit.unit_original')->label('Unit'),
                TextColumn::make('confidence')->label('Confidence')->badge(),
                TextColumn::make('readiness')->label('Readiness')->badge(),
                TextColumn::make('lifecycle')->label('Lifecycle')->badge(),
                TextColumn::make('financing_type')->label('Financing')->badge(),
            ])
            ->filters([
                SelectFilter::make('readiness')->options(MigrationReadiness::class),
                SelectFilter::make('confidence')->options([
                    'EXACT' => 'EXACT', 'HIGH' => 'HIGH', 'MEDIUM' => 'MEDIUM', 'AMBIGUOUS' => 'AMBIGUOUS', 'UNRESOLVED' => 'UNRESOLVED',
                ]),
                SelectFilter::make('financing_type')->options([
                    'KPR_SUBSIDI' => 'KPR_SUBSIDI', 'CASH' => 'CASH', 'UNRESOLVED' => 'UNRESOLVED',
                ]),
            ])
            ->defaultSort('created_at', 'desc');
    }
}
