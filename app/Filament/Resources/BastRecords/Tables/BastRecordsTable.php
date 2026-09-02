<?php

namespace App\Filament\Resources\BastRecords\Tables;

use App\BastStatus;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

class BastRecordsTable
{
    public static function configure(Table $table): Table
    {
        return $table->columns([
            TextColumn::make('salesCase.consumer.name')->label('Konsumen')->searchable(), TextColumn::make('salesCase.consumer.nik')->label('NIK')->searchable(),
            TextColumn::make('salesCase.project.name')->label('Proyek'), TextColumn::make('salesCase.unit.unit_code')->label('Unit')->searchable(),
            TextColumn::make('akad.akad_date')->label('Tanggal Akad')->date(), TextColumn::make('akad.document_number')->label('Nomor Akad')->searchable(),
            TextColumn::make('bast_number')->label('Nomor BAST')->searchable()->placeholder('-'), TextColumn::make('bast_date')->label('Tanggal BAST')->date()->sortable(),
            TextColumn::make('status')->badge(), TextColumn::make('updated_at')->dateTime()->sortable(),
        ])->filters([SelectFilter::make('status')->options(BastStatus::class)])->defaultSort('bast_date', 'desc');
    }
}
