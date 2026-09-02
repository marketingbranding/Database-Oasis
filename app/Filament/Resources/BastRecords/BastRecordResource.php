<?php

namespace App\Filament\Resources\BastRecords;

use App\Filament\Resources\BastRecords\Pages\CreateBastRecord;
use App\Filament\Resources\BastRecords\Pages\ListBastRecords;
use App\Filament\Resources\BastRecords\Schemas\BastRecordForm;
use App\Filament\Resources\BastRecords\Tables\BastRecordsTable;
use App\Models\BastRecord;
use App\Models\User;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use UnitEnum;

class BastRecordResource extends Resource
{
    protected static ?string $model = BastRecord::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedKey;

    protected static string|UnitEnum|null $navigationGroup = 'Proses Penjualan';

    protected static ?string $navigationLabel = 'BAST';

    public static function form(Schema $schema): Schema
    {
        return BastRecordForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return BastRecordsTable::configure($table);
    }

    public static function getEloquentQuery(): Builder
    {
        $query = parent::getEloquentQuery()->with(['salesCase.consumer', 'salesCase.project', 'salesCase.unit', 'akad']);
        $user = User::current();

        return $user?->isBranchScoped() ? $query->whereHas('salesCase', fn (Builder $q) => $q->where('branch_id', $user->branch_id)) : $query;
    }

    public static function getPages(): array
    {
        return ['index' => ListBastRecords::route('/'), 'create' => CreateBastRecord::route('/create')];
    }
}
