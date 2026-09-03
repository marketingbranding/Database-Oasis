<?php

namespace App\Filament\Resources\AkadTargets\Tables;

use App\Models\Branch;
use App\Models\User;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

class AkadTargetsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('period_month')->label('Bulan')->date('F Y')->sortable(),
                TextColumn::make('branch.name')->label('Cabang')->searchable()->sortable(),
                TextColumn::make('project.name')->label('Proyek')->placeholder('Target Cabang')->searchable(),
                TextColumn::make('target')->label('Target')->numeric()->sortable(),
                TextColumn::make('updated_at')->label('Diubah')->dateTime()->sortable(),
            ])
            ->filters([
                SelectFilter::make('branch')->options(fn (): array => Branch::query()
                    ->when(User::current()?->isBranchScoped(), fn ($query) => $query->whereKey(User::current()?->branch_id))
                    ->orderBy('name')->pluck('name', 'id')->all())->searchable(),
                SelectFilter::make('project')->relationship('project', 'name')->searchable()->preload(),
            ])
            ->recordActions([
                EditAction::make()->label('Ubah'),
                DeleteAction::make()->label('Hapus'),
            ])
            ->defaultSort('period_month', 'desc');
    }
}
