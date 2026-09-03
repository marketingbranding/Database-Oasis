<?php

namespace App\Filament\Resources\AkadRecords;

use App\Filament\Resources\AkadRecords\Pages\CreateAkadRecord;
use App\Filament\Resources\AkadRecords\Pages\ListAkadRecords;
use App\Filament\Resources\AkadRecords\Schemas\AkadRecordForm;
use App\Filament\Resources\AkadRecords\Tables\AkadRecordsTable;
use App\Models\AkadRecord;
use App\Models\User;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use UnitEnum;

class AkadRecordResource extends Resource
{
    protected static ?string $model = AkadRecord::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedScale;

    protected static string|UnitEnum|null $navigationGroup = 'Proses Penjualan';

    protected static ?int $navigationSort = 5;

    protected static ?string $navigationLabel = 'Akad';

    public static function form(Schema $schema): Schema
    {
        return AkadRecordForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return AkadRecordsTable::configure($table);
    }

    public static function getEloquentQuery(): Builder
    {
        $query = parent::getEloquentQuery()->with(['salesCase.consumer', 'salesCase.project', 'salesCase.unit', 'developerPpjb']);
        $user = User::current();

        return $user?->isBranchScoped() ? $query->whereHas('salesCase', fn (Builder $q) => $q->where('branch_id', $user->branch_id)) : $query;
    }

    public static function getPages(): array
    {
        return ['index' => ListAkadRecords::route('/'), 'create' => CreateAkadRecord::route('/create')];
    }
}
