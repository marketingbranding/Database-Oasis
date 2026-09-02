<?php

namespace App\Filament\Resources\AkadRecords\Tables;

use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class AkadRecordsTable
{
    public static function configure(Table $table): Table
    {
        return $table->columns([
            TextColumn::make('salesCase.consumer.name')->label('Konsumen')->searchable(), TextColumn::make('salesCase.consumer.nik')->label('NIK')->searchable(),
            TextColumn::make('salesCase.project.name')->label('Proyek'), TextColumn::make('salesCase.unit.unit_code')->label('Unit')->searchable(),
            TextColumn::make('salesCase.financing_type')->label('Financing')->badge(), TextColumn::make('developerPpjb.document_number')->label('PPJB')->searchable(),
            TextColumn::make('akad_date')->label('Tanggal Akad')->date()->sortable(), TextColumn::make('document_number')->label('Nomor Akad')->searchable()->placeholder('-'),
            TextColumn::make('akad_quality')->label('Kualitas')->placeholder('-'), TextColumn::make('updated_at')->dateTime()->sortable(),
        ])->defaultSort('akad_date', 'desc');
    }
}
