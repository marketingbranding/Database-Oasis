<?php

namespace App\Filament\Resources\AkadTargets\Schemas;

use App\Models\Branch;
use App\Models\Project;
use Carbon\Carbon;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Schema;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Validation\Rules\Unique;

class AkadTargetForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema->components([
            Select::make('branch_id')
                ->label('Cabang')
                ->options(fn (): array => Branch::query()->orderBy('name')->pluck('name', 'id')->all())
                ->live()
                ->afterStateUpdated(fn ($set) => $set('project_id', null))
                ->searchable()
                ->required(),
            Select::make('project_id')
                ->label('Proyek (opsional)')
                ->options(fn (Get $get): array => Project::query()
                    ->when($get('branch_id'), fn (Builder $query, string $branchId) => $query->where('branch_id', $branchId))
                    ->orderBy('name')
                    ->pluck('name', 'id')
                    ->all())
                ->searchable(),
            DatePicker::make('period_month')
                ->label('Bulan Target')
                ->displayFormat('F Y')
                ->default(now()->startOfMonth())
                ->required()
                ->unique(
                    ignoreRecord: true,
                    modifyRuleUsing: function (Unique $rule, Get $get): Unique {
                        $period = filled($get('period_month')) ? Carbon::parse($get('period_month'))->startOfMonth()->toDateString() : $get('period_month');

                        return $rule
                            ->using(fn (\Illuminate\Database\Query\Builder $query): \Illuminate\Database\Query\Builder => $query
                                ->where('branch_id', $get('branch_id'))
                                ->where('project_id', $get('project_id'))
                                ->whereDate('period_month', $period));
                    },
                ),
            TextInput::make('target')
                ->label('Target Akad')
                ->numeric()
                ->minValue(0)
                ->required(),
        ]);
    }
}
