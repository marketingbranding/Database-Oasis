<?php

namespace App\Filament\Resources\DeveloperPpjbs\Tables;

use App\DeveloperPpjbStatus;
use App\Models\DeveloperPpjb;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

class DeveloperPpjbsTable
{
    public static function configure(Table $table): Table
    {
        return $table->columns([
            TextColumn::make('salesCase.consumer.name')->label('Konsumen')->searchable(), TextColumn::make('salesCase.consumer.nik')->label('NIK')->searchable(),
            TextColumn::make('salesCase.project.name')->label('Proyek'), TextColumn::make('salesCase.unit.unit_code')->label('Unit')->searchable(),
            TextColumn::make('salesCase.financing_type')->label('Financing')->badge(), TextColumn::make('ppjb_identifier')->label('PPJB')->state(fn (DeveloperPpjb $record): string => $record->displayPpjbIdentifier()),
            TextColumn::make('document_date')->label('Tanggal')->date()->sortable(), TextColumn::make('status')->badge(),
            TextColumn::make('sp3k_identifier')->label('SP3K')->state(fn (DeveloperPpjb $record): string => $record->bankProcess?->displaySp3kIdentifier() ?? '-'), TextColumn::make('updated_at')->dateTime()->sortable(),
        ])->filters([SelectFilter::make('status')->options(DeveloperPpjbStatus::class)])->defaultSort('document_date', 'desc');
    }
}
