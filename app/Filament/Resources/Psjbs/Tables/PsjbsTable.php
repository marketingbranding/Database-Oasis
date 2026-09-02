<?php

namespace App\Filament\Resources\Psjbs\Tables;

use App\Models\Branch;
use App\Models\Project;
use App\PsjbStatus;
use Filament\Forms\Components\DatePicker;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class PsjbsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('salesCase.consumer.name')
                    ->label('Konsumen')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('salesCase.consumer.nik')
                    ->label('NIK')
                    ->searchable(),
                TextColumn::make('salesCase.project.name')
                    ->label('Proyek')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('salesCase.unit.unit_code')
                    ->label('Unit')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('psjb_date')
                    ->label('Tanggal PSJB')
                    ->date()
                    ->sortable(),
                TextColumn::make('document_number')
                    ->label('Nomor Dokumen')
                    ->searchable()
                    ->placeholder('-'),
                TextColumn::make('status')
                    ->label('Status')
                    ->badge()
                    ->formatStateUsing(fn (PsjbStatus $state): string => $state->getLabel())
                    ->sortable(),
                TextColumn::make('coordinator.name')
                    ->label('Koordinator')
                    ->placeholder('-'),
                TextColumn::make('createdBy.name')
                    ->label('Dibuat Oleh')
                    ->placeholder('-'),
                TextColumn::make('updated_at')
                    ->label('Diubah')
                    ->dateTime()
                    ->sortable(),
            ])
            ->filters([
                SelectFilter::make('branch')
                    ->label('Cabang')
                    ->options(fn (): array => Branch::query()->orderBy('name')->pluck('name', 'id')->all())
                    ->query(fn (Builder $query, array $data): Builder => filled($data['value'] ?? null)
                        ? $query->whereHas('salesCase', fn (Builder $query) => $query->where('branch_id', $data['value']))
                        : $query),
                SelectFilter::make('project')
                    ->label('Proyek')
                    ->options(fn (): array => Project::query()->orderBy('name')->pluck('name', 'id')->all())
                    ->query(fn (Builder $query, array $data): Builder => filled($data['value'] ?? null)
                        ? $query->whereHas('salesCase', fn (Builder $query) => $query->where('project_id', $data['value']))
                        : $query),
                SelectFilter::make('status')
                    ->label('Status')
                    ->options(PsjbStatus::class),
                Filter::make('psjb_date')
                    ->label('Tanggal PSJB')
                    ->form([
                        DatePicker::make('psjb_date_from')
                            ->label('Dari'),
                        DatePicker::make('psjb_date_until')
                            ->label('Sampai'),
                    ])
                    ->query(fn (Builder $query, array $data): Builder => $query
                        ->when(filled($data['psjb_date_from'] ?? null), fn (Builder $query) => $query->whereDate('psjb_date', '>=', $data['psjb_date_from']))
                        ->when(filled($data['psjb_date_until'] ?? null), fn (Builder $query) => $query->whereDate('psjb_date', '<=', $data['psjb_date_until']))),
            ])
            ->defaultSort('psjb_date', 'desc');
    }
}
