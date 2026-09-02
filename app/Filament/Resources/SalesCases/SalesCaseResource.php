<?php

namespace App\Filament\Resources\SalesCases;

use App\Filament\Resources\SalesCases\Pages\CreateSalesCase;
use App\Filament\Resources\SalesCases\Pages\EditSalesCase;
use App\Filament\Resources\SalesCases\Pages\ListSalesCases;
use App\Filament\Resources\SalesCases\Pages\ViewSalesCase;
use App\Filament\Resources\SalesCases\RelationManagers\BankProcessesRelationManager;
use App\Filament\Resources\SalesCases\RelationManagers\BiChecksRelationManager;
use App\Filament\Resources\SalesCases\RelationManagers\DocumentSubmissionsRelationManager;
use App\Filament\Resources\SalesCases\RelationManagers\PsjbsRelationManager;
use App\Filament\Resources\SalesCases\Schemas\SalesCaseForm;
use App\Filament\Resources\SalesCases\Schemas\SalesCaseInfolist;
use App\Filament\Resources\SalesCases\Tables\SalesCasesTable;
use App\Models\SalesCase;
use App\Models\User;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use UnitEnum;

class SalesCaseResource extends Resource
{
    protected static ?string $model = SalesCase::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedClipboardDocumentList;

    protected static string|UnitEnum|null $navigationGroup = 'Operasional';

    protected static ?string $navigationLabel = 'Sales Cases';

    protected static ?string $modelLabel = 'Sales Case';

    protected static ?string $pluralModelLabel = 'Sales Cases';

    public static function form(Schema $schema): Schema
    {
        return SalesCaseForm::configure($schema);
    }

    public static function infolist(Schema $schema): Schema
    {
        return SalesCaseInfolist::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return SalesCasesTable::configure($table);
    }

    public static function getEloquentQuery(): Builder
    {
        $query = parent::getEloquentQuery()
            ->with(['consumer', 'branch', 'project', 'unit']);

        $user = User::current();
        if ($user?->isBranchScoped()) {
            $query->where('branch_id', $user->branch_id);
        }

        return $query;
    }

    public static function getRelations(): array
    {
        return [
            BiChecksRelationManager::class,
            PsjbsRelationManager::class,
            DocumentSubmissionsRelationManager::class,
            BankProcessesRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListSalesCases::route('/'),
            'create' => CreateSalesCase::route('/create'),
            'view' => ViewSalesCase::route('/{record}'),
            'edit' => EditSalesCase::route('/{record}/edit'),
        ];
    }
}
