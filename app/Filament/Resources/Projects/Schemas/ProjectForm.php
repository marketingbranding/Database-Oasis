<?php

namespace App\Filament\Resources\Projects\Schemas;

use App\Models\Project;
use App\Models\User;
use App\ProjectStatus;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Schema;
use Illuminate\Validation\Rules\Unique;

class ProjectForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Select::make('branch_id')
                    ->label('Cabang')
                    ->relationship('branch', 'name')
                    ->searchable()
                    ->preload()
                    ->required()
                    ->default(fn (): ?string => User::current()?->isBranchScoped() ? User::current()->branch_id : null)
                    ->disabled(fn ($record): bool => (User::current()?->isBranchScoped() ?? false)
                        || ($record instanceof Project && $record->salesCases()->exists())),
                TextInput::make('code')
                    ->label('Kode')
                    ->required()
                    ->maxLength(255)
                    ->unique(
                        ignoreRecord: true,
                        modifyRuleUsing: fn (Unique $rule, Get $get): Unique => $rule->where(
                            'branch_id',
                            $get('branch_id') ?? User::current()?->branch_id,
                        ),
                    ),
                TextInput::make('name')
                    ->label('Nama')
                    ->required()
                    ->maxLength(255),
                TextInput::make('location')
                    ->label('Lokasi')
                    ->required()
                    ->maxLength(255),
                Select::make('status')
                    ->label('Status')
                    ->options(ProjectStatus::class)
                    ->default(ProjectStatus::Aktif)
                    ->required(),
            ]);
    }
}
