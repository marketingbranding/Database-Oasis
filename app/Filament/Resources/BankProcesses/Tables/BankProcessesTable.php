<?php

namespace App\Filament\Resources\BankProcesses\Tables;

use App\BankResponseType;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

class BankProcessesTable
{
    public static function configure(Table $table): Table
    {
        return $table->columns([
            TextColumn::make('salesCase.consumer.name')->label('Konsumen')->searchable()->sortable(),
            TextColumn::make('salesCase.consumer.nik')->label('NIK')->searchable(),
            TextColumn::make('salesCase.project.name')->label('Proyek')->searchable(),
            TextColumn::make('salesCase.unit.unit_code')->label('Unit')->searchable(),
            TextColumn::make('bank.name')->label('Bank')->searchable()->sortable(),
            TextColumn::make('documentSubmission.sequence')->label('Submission #'),
            TextColumn::make('response_type')->label('Response')->badge()->formatStateUsing(fn (BankResponseType $state): string => $state->getLabel()),
            TextColumn::make('response_date')->label('Tanggal')->date()->sortable(),
            TextColumn::make('sp3k_number')->label('SP3K')->searchable()->placeholder('-'),
            IconColumn::make('is_authoritative')->label('Authoritative')->boolean(),
            TextColumn::make('updated_at')->label('Diubah')->dateTime()->sortable(),
        ])->filters([
            SelectFilter::make('bank')->relationship('bank', 'name')->searchable()->preload(),
            SelectFilter::make('response_type')->options(BankResponseType::class),
        ])->defaultSort('response_date', 'desc');
    }
}
