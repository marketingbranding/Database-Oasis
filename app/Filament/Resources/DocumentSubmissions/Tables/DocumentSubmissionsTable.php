<?php

namespace App\Filament\Resources\DocumentSubmissions\Tables;

use App\DocumentSubmissionStatus;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

class DocumentSubmissionsTable
{
    public static function configure(Table $table): Table
    {
        return $table->columns([
            TextColumn::make('salesCase.consumer.name')->label('Konsumen')->searchable()->sortable(),
            TextColumn::make('salesCase.consumer.nik')->label('NIK')->searchable(),
            TextColumn::make('salesCase.project.name')->label('Proyek')->searchable(),
            TextColumn::make('salesCase.unit.unit_code')->label('Unit')->searchable(),
            TextColumn::make('bank.name')->label('Bank')->searchable()->sortable(),
            TextColumn::make('sequence')->label('Submission #')->sortable(),
            TextColumn::make('submission_date')->label('Tanggal')->date()->sortable(),
            TextColumn::make('latestBankProcess.response_type')->label('Response Terakhir')->badge()->placeholder('-'),
            TextColumn::make('latestBankProcess.sp3k_number')->label('SP3K')->searchable()->placeholder('-'),
            TextColumn::make('status')->badge()->formatStateUsing(fn (DocumentSubmissionStatus $state): string => $state->getLabel()),
            TextColumn::make('updated_at')->label('Diubah')->dateTime()->sortable(),
        ])->filters([
            SelectFilter::make('bank')->relationship('bank', 'name')->searchable()->preload(),
            SelectFilter::make('status')->options(DocumentSubmissionStatus::class),
        ])->defaultSort('created_at', 'desc');
    }
}
