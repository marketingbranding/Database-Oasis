<?php

namespace App\Filament\Resources\DeveloperPpjbs;

use App\Filament\Resources\DeveloperPpjbs\Pages\CreateDeveloperPpjb;
use App\Filament\Resources\DeveloperPpjbs\Pages\ListDeveloperPpjbs;
use App\Filament\Resources\DeveloperPpjbs\Schemas\DeveloperPpjbForm;
use App\Filament\Resources\DeveloperPpjbs\Tables\DeveloperPpjbsTable;
use App\Models\DeveloperPpjb;
use App\Models\User;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use UnitEnum;

class DeveloperPpjbResource extends Resource
{
    protected static ?string $model = DeveloperPpjb::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedDocumentCheck;

    protected static string|UnitEnum|null $navigationGroup = 'Proses Penjualan';

    protected static ?int $navigationSort = 4;

    protected static ?string $navigationLabel = 'PPJB Developer';

    public static function form(Schema $schema): Schema
    {
        return DeveloperPpjbForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return DeveloperPpjbsTable::configure($table);
    }

    public static function getEloquentQuery(): Builder
    {
        $query = parent::getEloquentQuery()->with(['salesCase.consumer', 'salesCase.project', 'salesCase.unit', 'bankProcess']);
        $user = User::current();

        return $user?->isBranchScoped() ? $query->whereHas('salesCase', fn (Builder $q) => $q->where('branch_id', $user->branch_id)) : $query;
    }

    public static function getPages(): array
    {
        return ['index' => ListDeveloperPpjbs::route('/'), 'create' => CreateDeveloperPpjb::route('/create')];
    }
}
