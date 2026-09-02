<?php

namespace App\Filament\Resources\SalesCases\Tables;

use App\Filament\Resources\SalesCases\Actions\CaseWorkflowActions;
use App\FinancingType;
use App\SalesCaseStage;
use App\SalesCaseStatus;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

class SalesCasesTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('consumer.name')
                    ->label('Konsumen')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('consumer.nik')
                    ->label('NIK')
                    ->searchable(),
                TextColumn::make('branch.name')
                    ->label('Cabang')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('project.name')
                    ->label('Proyek')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('unit.unit_code')
                    ->label('Unit')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('financing_type')
                    ->label('Pembiayaan')
                    ->badge()
                    ->formatStateUsing(fn (FinancingType $state): string => $state->getLabel())
                    ->sortable(),
                TextColumn::make('current_stage')
                    ->label('Stage')
                    ->badge()
                    ->formatStateUsing(fn (SalesCaseStage $state): string => $state->getLabel())
                    ->sortable(),
                TextColumn::make('case_status')
                    ->label('Status')
                    ->badge()
                    ->formatStateUsing(fn (SalesCaseStatus $state): string => $state->getLabel())
                    ->sortable(),
                TextColumn::make('booking_date')
                    ->label('Booking')
                    ->date()
                    ->sortable(),
                TextColumn::make('latestSubmission.bank.name')
                    ->label('Bank')
                    ->placeholder('-')
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('currentApprovedBankProcess.sp3k_number')
                    ->label('SP3K')
                    ->placeholder('-')
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('daysInCurrentStage')
                    ->label('Hari di Stage')
                    ->state(fn ($record): string => $record->daysInCurrentStage() === null
                        ? '-'
                        : $record->daysInCurrentStage().' hari'),
                TextColumn::make('updated_at')
                    ->label('Diubah')
                    ->dateTime()
                    ->sortable(),
            ])
            ->filters([
                SelectFilter::make('branch')
                    ->label('Cabang')
                    ->relationship('branch', 'name')
                    ->searchable()
                    ->preload(),
                SelectFilter::make('project')
                    ->label('Proyek')
                    ->relationship('project', 'name')
                    ->searchable()
                    ->preload(),
                SelectFilter::make('unit')
                    ->label('Unit')
                    ->relationship('unit', 'unit_code')
                    ->searchable(),
                SelectFilter::make('financing_type')
                    ->label('Pembiayaan')
                    ->options(FinancingType::class),
                SelectFilter::make('case_status')
                    ->label('Status')
                    ->options(SalesCaseStatus::class),
                SelectFilter::make('current_stage')
                    ->label('Stage')
                    ->options(SalesCaseStage::class),
            ])
            ->recordActions([
                ViewAction::make()
                    ->label('Lihat'),
                EditAction::make()
                    ->label('Ubah'),
                CaseWorkflowActions::mundur(),
                CaseWorkflowActions::reject(),
                CaseWorkflowActions::cancel(),
                CaseWorkflowActions::move(),
            ])
            ->defaultSort('created_at', 'desc');
    }
}
