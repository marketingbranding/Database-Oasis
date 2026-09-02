<?php

namespace App\Filament\Resources\BiChecks\Tables;

use App\BiCheckResult;
use App\Models\Branch;
use App\Models\Project;
use Filament\Forms\Components\DatePicker;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class BiChecksTable
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
                TextColumn::make('check_date')
                    ->label('Tanggal Cek')
                    ->date()
                    ->sortable(),
                TextColumn::make('result')
                    ->label('Hasil')
                    ->badge()
                    ->formatStateUsing(fn (BiCheckResult $state): string => $state->getLabel())
                    ->sortable(),
                TextColumn::make('createdBy.name')
                    ->label('Dicatat Oleh')
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
                SelectFilter::make('result')
                    ->label('Hasil')
                    ->options(BiCheckResult::class),
                Filter::make('check_date')
                    ->label('Tanggal Cek')
                    ->form([
                        DatePicker::make('check_date_from')
                            ->label('Dari'),
                        DatePicker::make('check_date_until')
                            ->label('Sampai'),
                    ])
                    ->query(fn (Builder $query, array $data): Builder => $query
                        ->when(filled($data['check_date_from'] ?? null), fn (Builder $query) => $query->whereDate('check_date', '>=', $data['check_date_from']))
                        ->when(filled($data['check_date_until'] ?? null), fn (Builder $query) => $query->whereDate('check_date', '<=', $data['check_date_until']))),
            ])
            ->defaultSort('check_date', 'desc');
    }
}
