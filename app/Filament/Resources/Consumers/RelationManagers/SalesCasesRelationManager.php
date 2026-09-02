<?php

namespace App\Filament\Resources\Consumers\RelationManagers;

use App\Filament\Resources\SalesCases\SalesCaseResource;
use App\Models\User;
use Filament\Actions\Action;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

/**
 * Authorized Sales Case history for one Consumer. Branch users only see
 * cases from their own branch — visibility, never data merging.
 */
class SalesCasesRelationManager extends RelationManager
{
    protected static string $relationship = 'salesCases';

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('id')
            ->modifyQueryUsing(function (Builder $query): Builder {
                $user = User::current();

                return $user?->isBranchScoped()
                    ? $query->where('branch_id', $user->branch_id)
                    : $query;
            })
            ->columns([
                TextColumn::make('branch.name')->label('Cabang'),
                TextColumn::make('project.name')->label('Proyek'),
                TextColumn::make('unit.unit_code')->label('Unit'),
                TextColumn::make('financing_type')->label('Pembiayaan')->badge(),
                TextColumn::make('current_stage')->label('Stage')->badge(),
                TextColumn::make('case_status')->label('Status')->badge()
                    ->color(fn ($state): string => match ($state?->value) {
                        'ACTIVE' => 'success',
                        'COMPLETED' => 'primary',
                        default => 'gray',
                    }),
                TextColumn::make('booking_date')->label('Booking')->date(),
                TextColumn::make('closed_at')->label('Ditutup')->dateTime()->placeholder('-'),
            ])
            ->recordActions([
                Action::make('openWorkspace')->label('Buka Workspace')
                    ->icon('heroicon-o-arrow-top-right-on-square')
                    ->url(fn ($record): string => SalesCaseResource::getUrl('view', ['record' => $record])),
            ])
            ->defaultSort('created_at', 'desc');
    }
}
