<?php

namespace App\Filament\Resources\SalesCases\Schemas;

use App\Filament\Resources\SalesCases\SalesCaseResource;
use App\FinancingType;
use App\Models\SalesCase;
use App\SalesCaseStage;
use App\SalesCaseStatus;
use App\Services\SalesCaseTimelineService;
use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\View;
use Filament\Schemas\Schema;

class SalesCaseInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Ringkasan Proses')
                    ->schema([
                        View::make('filament.sales-case.process-summary')
                            ->viewData(fn (SalesCase $record): array => ['case' => $record])
                            ->columnSpanFull(),
                        View::make('filament.sales-case.stepper')
                            ->viewData(fn (SalesCase $record): array => ['case' => $record])
                            ->columnSpanFull(),
                    ]),
                Section::make('Konsumen')
                    ->columns(3)
                    ->schema([
                        TextEntry::make('consumer.name')->label('Nama'),
                        TextEntry::make('consumer.nik')->label('NIK'),
                        TextEntry::make('consumer.phone')->label('Telepon')->placeholder('-'),
                    ]),
                Section::make('Properti')
                    ->columns(3)
                    ->schema([
                        TextEntry::make('branch.name')->label('Cabang'),
                        TextEntry::make('project.name')->label('Proyek'),
                        TextEntry::make('unit.unit_code')->label('Unit / Kavling'),
                    ]),
                Section::make('Transaksi')
                    ->columns(3)
                    ->schema([
                        TextEntry::make('financing_type')->label('Tipe Pembiayaan')
                            ->badge()
                            ->formatStateUsing(fn (FinancingType $state): string => $state->getLabel()),
                        TextEntry::make('salesPic.name')->label('PIC Sales')->placeholder('-'),
                        TextEntry::make('coordinator.name')->label('Koordinator')->placeholder('-'),
                        TextEntry::make('booking_date')->label('Tanggal Booking')->date()->placeholder('-'),
                        TextEntry::make('current_stage')->label('Stage')
                            ->badge()
                            ->formatStateUsing(fn (SalesCaseStage $state): string => $state->getLabel()),
                        TextEntry::make('case_status')->label('Status')
                            ->badge()
                            ->formatStateUsing(fn (SalesCaseStatus $state): string => $state->getLabel()),
                    ]),
                Section::make('Akad Readiness')
                    ->columns(4)
                    ->schema([
                        TextEntry::make('akadReadiness.building_progress')->label('Progress Bangunan')->suffix('%')->placeholder('-'),
                        TextEntry::make('akadReadiness.building_status')->label('Bangunan')->badge()->placeholder('UNKNOWN'),
                        TextEntry::make('akadReadiness.dp_status')->label('DP')->badge()->placeholder('UNKNOWN'),
                        TextEntry::make('akadReadiness.electricity_status')->label('Listrik')->badge()->placeholder('UNKNOWN'),
                        TextEntry::make('akadReadiness.water_status')->label('Air')->badge()->placeholder('UNKNOWN'),
                        TextEntry::make('akadReadiness.consumer_status')->label('Konsumen')->badge()->placeholder('UNKNOWN'),
                        TextEntry::make('akadReadiness.consumer_note')->label('Catatan Konsumen')->placeholder('-'),
                        TextEntry::make('readiness_issue_count')->label('Jumlah Kendala')
                            ->state(fn (SalesCase $record): int => $record->akadReadiness?->issueCount() ?? 0),
                    ]),
                Section::make('Status Akhir')
                    ->visible(fn (SalesCase $record): bool => $record->case_status !== SalesCaseStatus::Active)
                    ->columns(3)
                    ->schema([
                        TextEntry::make('closed_at')->label('Ditutup')->dateTime()->placeholder('-'),
                        TextEntry::make('closed_reason')->label('Alasan Penutupan')->placeholder('-')
                            ->visible(fn (SalesCase $record): bool => $record->closed_reason !== null),
                        TextEntry::make('transfer_reason')->label('Alasan Pindah Kavling')->placeholder('-')
                            ->visible(fn (SalesCase $record): bool => $record->transfer_reason !== null),
                        TextEntry::make('akad.akad_date')->label('Tanggal Akad')->date()->placeholder('-')
                            ->visible(fn (SalesCase $record): bool => $record->akad !== null),
                        TextEntry::make('bast.bast_date')->label('Tanggal BAST')->date()->placeholder('-')
                            ->visible(fn (SalesCase $record): bool => $record->bast !== null),
                        TextEntry::make('unit.status')->label('Status Unit')
                            ->badge()
                            ->visible(fn (SalesCase $record): bool => $record->case_status === SalesCaseStatus::Completed),
                    ]),
                Section::make('Pindah Kavling')
                    ->visible(fn (SalesCase $record): bool => $record->previous_case_id !== null || $record->successorCase()->exists())
                    ->columns(2)
                    ->schema([
                        TextEntry::make('previous_case_id')->label('Case Sebelumnya')
                            ->visible(fn (SalesCase $record): bool => $record->previous_case_id !== null)
                            ->url(fn (SalesCase $record): ?string => $record->previous_case_id === null
                                ? null
                                : SalesCaseResource::getUrl('view', ['record' => $record->previous_case_id]))
                            ->formatStateUsing(fn (SalesCase $record): ?string => $record->previous_case_id === null
                                ? null
                                : sprintf(
                                    '%s — unit %s',
                                    $record->previousCase?->consumer->name ?? '-',
                                    $record->previousCase?->unit->unit_code ?? '-',
                                )),
                        TextEntry::make('successor_case')->label('Pindah Kavling ke')
                            ->visible(fn (SalesCase $record): bool => $record->successorCase()->exists())
                            ->state(fn (SalesCase $record): ?string => $record->successorCase === null ? null : $record->successorCase->unit->unit_code)
                            ->url(fn (SalesCase $record): ?string => $record->successorCase === null
                                ? null
                                : SalesCaseResource::getUrl('view', ['record' => $record->successorCase])),
                    ]),
                Section::make('Timeline')
                    ->schema([
                        View::make('filament.sales-case.timeline')
                            ->viewData(fn (SalesCase $record): array => [
                                'items' => app(SalesCaseTimelineService::class)->forCase($record),
                            ])
                            ->columnSpanFull(),
                    ]),
            ]);
    }
}
