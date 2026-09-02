<?php

namespace App\Filament\Resources\Psjbs;

use App\Filament\Resources\Psjbs\Pages\CreatePsjb;
use App\Filament\Resources\Psjbs\Pages\ListPsjbs;
use App\Filament\Resources\Psjbs\Schemas\PsjbForm;
use App\Filament\Resources\Psjbs\Tables\PsjbsTable;
use App\Models\Psjb;
use App\Models\User;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use UnitEnum;

class PsjbResource extends Resource
{
    protected static ?string $model = Psjb::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedDocumentText;

    protected static string|UnitEnum|null $navigationGroup = 'Proses Penjualan';

    protected static ?string $navigationLabel = 'PSJB';

    protected static ?string $modelLabel = 'PSJB';

    protected static ?string $pluralModelLabel = 'PSJB';

    public static function form(Schema $schema): Schema
    {
        return PsjbForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return PsjbsTable::configure($table);
    }

    public static function getEloquentQuery(): Builder
    {
        $query = parent::getEloquentQuery()
            ->with(['salesCase.consumer', 'salesCase.project', 'salesCase.unit', 'coordinator', 'createdBy']);

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
            'index' => ListPsjbs::route('/'),
            'create' => CreatePsjb::route('/create'),
        ];
    }
}
