<?php

namespace App\Filament\Resources\Units\RelationManagers;

use App\Filament\Resources\SalesCases\SalesCaseResource;
use App\Models\User;
use Filament\Actions\Action;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

/**
 * Authorized Sales Case history for one Unit, derived from real Sales Case
 * relations — never from UnitStatus. ACTIVE case is visually distinguished
 * from historical cases.
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
                    ? $query->whereHas('project', fn (Builder $query) => $query->where('branch_id', $user->branch_id))
                    : $query;
            })
            ->columns([
                TextColumn::make('consumer.name')->label('Konsumen'),
                TextColumn::make('financing_type')->label('Pembiayaan')->badge(),
                TextColumn::make('current_stage')->label('Stage')->badge(),
                TextColumn::make('case_status')->label('Status')->badge()
                    ->icon(fn ($state): string => $state?->value === 'ACTIVE' ? 'heroicon-m-play' : 'heroicon-o-clock')
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
