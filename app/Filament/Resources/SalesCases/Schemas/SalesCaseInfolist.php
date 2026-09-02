<?php

namespace App\Filament\Resources\SalesCases\Schemas;

use App\Filament\Resources\SalesCases\SalesCaseResource;
use App\FinancingType;
use App\Models\SalesCase;
use App\SalesCaseStage;
use App\SalesCaseStatus;
use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class SalesCaseInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Konsumen')
                    ->schema([
                        TextEntry::make('consumer.name')
                            ->label('Nama'),
                        TextEntry::make('consumer.nik')
                            ->label('NIK'),
                        TextEntry::make('consumer.phone')
                            ->label('Telepon')
                            ->placeholder('-'),
                    ]),
                Section::make('Properti')
                    ->schema([
                        TextEntry::make('branch.name')
                            ->label('Cabang'),
                        TextEntry::make('project.name')
                            ->label('Proyek'),
                        TextEntry::make('unit.unit_code')
                            ->label('Unit / Kavling'),
                    ]),
                Section::make('Case')
                    ->schema([
                        TextEntry::make('financing_type')
                            ->label('Tipe Pembiayaan')
                            ->badge()
                            ->formatStateUsing(fn (FinancingType $state): string => $state->getLabel()),
                        TextEntry::make('source')
                            ->label('Sumber')
                            ->placeholder('-'),
                        TextEntry::make('booking_date')
                            ->label('Tanggal Booking')
                            ->date()
                            ->placeholder('-'),
                        TextEntry::make('current_stage')
                            ->label('Stage')
                            ->badge()
                            ->formatStateUsing(fn (SalesCaseStage $state): string => $state->getLabel()),
                        TextEntry::make('case_status')
                            ->label('Status')
                            ->badge()
                            ->formatStateUsing(fn (SalesCaseStatus $state): string => $state->getLabel()),
                        TextEntry::make('created_at')
                            ->label('Dibuat')
                            ->dateTime(),
                        TextEntry::make('createdBy.name')
                            ->label('Dibuat Oleh')
                            ->placeholder('-'),
                        TextEntry::make('closed_at')
                            ->label('Ditutup')
                            ->dateTime()
                            ->visible(fn (SalesCase $record): bool => $record->closed_at !== null)
                            ->placeholder('-'),
                        TextEntry::make('closed_reason')
                            ->label('Alasan Penutupan')
                            ->visible(fn (SalesCase $record): bool => $record->closed_reason !== null)
                            ->placeholder('-'),
                        TextEntry::make('transfer_reason')
                            ->label('Alasan Pindah Kavling')
                            ->visible(fn (SalesCase $record): bool => $record->transfer_reason !== null)
                            ->placeholder('-'),
                    ]),
                Section::make('Histori')
                    ->schema([
                        TextEntry::make('previous_case_id')
                            ->label('Case Sebelumnya')
                            ->visible(fn (SalesCase $record): bool => $record->previous_case_id !== null)
                            ->url(fn (SalesCase $record): ?string => $record->previous_case_id === null
                                ? null
                                : SalesCaseResource::getUrl('view', ['record' => $record->previous_case_id]))
                            ->formatStateUsing(fn (SalesCase $record): ?string => $record->previous_case_id === null
                                ? null
                                : "{$record->previousCase?->consumer?->name} — {$record->previousCase?->unit?->unit_code}"),
                    ]),
            ]);
    }
}
