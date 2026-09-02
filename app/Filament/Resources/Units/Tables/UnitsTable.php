<?php

namespace App\Filament\Resources\Units\Tables;

use App\Models\User;
use App\UnitStatus;
use App\UtilityStatus;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class UnitsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('project.branch.name')
                    ->label('Cabang')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('project.name')
                    ->label('Proyek')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('unit_code')
                    ->label('Kode Unit')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('block')
                    ->label('Blok')
                    ->searchable(),
                TextColumn::make('number')
                    ->label('Nomor')
                    ->searchable(),
                TextColumn::make('status')
                    ->label('Status')
                    ->badge()
                    ->formatStateUsing(fn (UnitStatus $state): string => $state->getLabel())
                    ->sortable(),
                TextColumn::make('building_progress')
                    ->label('Progres')
                    ->suffix('%')
                    ->sortable(),
                TextColumn::make('electricity_status')
                    ->label('Listrik')
                    ->badge()
                    ->formatStateUsing(fn (UtilityStatus $state): string => $state->getLabel()),
                TextColumn::make('water_status')
                    ->label('Air')
                    ->badge()
                    ->formatStateUsing(fn (UtilityStatus $state): string => $state->getLabel()),
            ])
            ->filters([
                SelectFilter::make('project')
                    ->label('Proyek')
                    ->relationship(
                        'project',
                        'name',
                        modifyQueryUsing: function (Builder $query): Builder {
                            $user = User::current();
                            if ($user?->isBranchScoped()) {
                                $query->where('branch_id', $user->branch_id);
                            }

                            return $query;
                        },
                    )
                    ->searchable()
                    ->preload(),
                SelectFilter::make('status')
                    ->label('Status')
                    ->options(UnitStatus::class),
            ])
            ->recordActions([
                EditAction::make()
                    ->label('Ubah'),
                DeleteAction::make()
                    ->label('Hapus'),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ])
            ->defaultSort('unit_code');
    }
}
