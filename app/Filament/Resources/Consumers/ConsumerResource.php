<?php

namespace App\Filament\Resources\Consumers;

use App\Filament\Resources\Consumers\Pages\CreateConsumer;
use App\Filament\Resources\Consumers\Pages\EditConsumer;
use App\Filament\Resources\Consumers\Pages\ListConsumers;
use App\Filament\Resources\Consumers\RelationManagers\SalesCasesRelationManager;
use App\Filament\Resources\Consumers\Schemas\ConsumerForm;
use App\Filament\Resources\Consumers\Tables\ConsumersTable;
use App\Models\Consumer;
use App\Models\User;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use UnitEnum;

class ConsumerResource extends Resource
{
    protected static ?string $model = Consumer::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedIdentification;

    protected static string|UnitEnum|null $navigationGroup = 'Operasional';

    protected static ?string $navigationLabel = 'Konsumen';

    protected static ?string $modelLabel = 'Konsumen';

    protected static ?string $pluralModelLabel = 'Konsumen';

    protected static ?int $navigationSort = 2;

    public static function form(Schema $schema): Schema
    {
        return ConsumerForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return ConsumersTable::configure($table);
    }

    public static function getRelations(): array
    {
        return [
            SalesCasesRelationManager::class,
        ];
    }

    public static function getEloquentQuery(): Builder
    {
        $query = parent::getEloquentQuery()
            ->with(['activeSalesCase.unit']);

        $user = User::current();
        if ($user?->isBranchScoped()) {
            $query->whereHas(
                'salesCases',
                fn (Builder $query) => $query->where('branch_id', $user->branch_id),
            );
        }

        return $query;
    }

    public static function getPages(): array
    {
        return [
            'index' => ListConsumers::route('/'),
            'create' => CreateConsumer::route('/create'),
            'edit' => EditConsumer::route('/{record}/edit'),
        ];
    }
}
