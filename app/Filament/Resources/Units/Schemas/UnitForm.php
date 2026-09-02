<?php

namespace App\Filament\Resources\Units\Schemas;

use App\Models\Unit;
use App\Models\User;
use App\UnitStatus;
use App\UtilityStatus;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Schema;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Validation\Rules\Exists;
use Illuminate\Validation\Rules\Unique;

class UnitForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Select::make('project_id')
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
                    ->preload()
                    ->required()
                    ->disabled(fn ($record): bool => $record instanceof Unit && $record->salesCases()->exists())
                    ->exists(
                        'projects',
                        'id',
                        modifyRuleUsing: function (Exists $rule): Exists {
                            $user = User::current();
                            if ($user?->isBranchScoped()) {
                                $rule->where('branch_id', $user->branch_id);
                            }

                            return $rule;
                        },
                    ),
                TextInput::make('unit_code')
                    ->label('Kode Unit')
                    ->required()
                    ->maxLength(255)
                    ->unique(
                        ignoreRecord: true,
                        modifyRuleUsing: fn (Unique $rule, Get $get): Unique => $rule->where('project_id', $get('project_id')),
                    ),
                TextInput::make('block')
                    ->label('Blok')
                    ->maxLength(255),
                TextInput::make('number')
                    ->label('Nomor')
                    ->maxLength(255),
                Select::make('status')
                    ->label('Status')
                    ->options(UnitStatus::class)
                    ->default(UnitStatus::Tersedia)
                    ->required(),
                TextInput::make('building_progress')
                    ->label('Progres Bangunan')
                    ->numeric()
                    ->minValue(0)
                    ->maxValue(100)
                    ->suffix('%'),
                Select::make('electricity_status')
                    ->label('Status Listrik')
                    ->options(UtilityStatus::class),
                Select::make('water_status')
                    ->label('Status Air')
                    ->options(UtilityStatus::class),
            ]);
    }
}
