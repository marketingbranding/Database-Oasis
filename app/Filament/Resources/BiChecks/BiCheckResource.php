<?php

namespace App\Filament\Resources\BiChecks;

use App\Filament\Resources\BiChecks\Pages\CreateBiCheck;
use App\Filament\Resources\BiChecks\Pages\ListBiChecks;
use App\Filament\Resources\BiChecks\Schemas\BiCheckForm;
use App\Filament\Resources\BiChecks\Tables\BiChecksTable;
use App\Models\BiCheck;
use App\Models\User;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use UnitEnum;

class BiCheckResource extends Resource
{
    protected static ?string $model = BiCheck::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedShieldCheck;

    protected static string|UnitEnum|null $navigationGroup = 'Operasional';

    protected static ?int $navigationSort = 4;

    protected static ?string $navigationLabel = 'BI Checking';

    protected static ?string $modelLabel = 'BI Check';

    protected static ?string $pluralModelLabel = 'BI Checking';

    public static function form(Schema $schema): Schema
    {
        return BiCheckForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return BiChecksTable::configure($table);
    }

    public static function getEloquentQuery(): Builder
    {
        $query = parent::getEloquentQuery()
            ->with(['salesCase.consumer', 'salesCase.project', 'salesCase.unit', 'createdBy']);

        $user = User::current();
        if ($user?->isBranchScoped()) {
            $query->whereHas(
                'salesCase',
                fn (Builder $query) => $query->where('branch_id', $user->branch_id),
            );
        }

        return $query;
    }

    public static function getPages(): array
    {
        return [
            'index' => ListBiChecks::route('/'),
            'create' => CreateBiCheck::route('/create'),
        ];
    }
}
