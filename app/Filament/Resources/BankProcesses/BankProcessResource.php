<?php

namespace App\Filament\Resources\BankProcesses;

use App\Filament\Resources\BankProcesses\Pages\CreateBankProcess;
use App\Filament\Resources\BankProcesses\Pages\ListBankProcesses;
use App\Filament\Resources\BankProcesses\Schemas\BankProcessForm;
use App\Filament\Resources\BankProcesses\Tables\BankProcessesTable;
use App\Models\BankProcess;
use App\Models\User;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use UnitEnum;

class BankProcessResource extends Resource
{
    protected static ?string $model = BankProcess::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedBuildingLibrary;

    protected static string|UnitEnum|null $navigationGroup = 'Proses Penjualan';

    protected static ?int $navigationSort = 3;

    protected static ?string $navigationLabel = 'Proses Bank';

    protected static ?string $modelLabel = 'Proses Bank';

    public static function form(Schema $schema): Schema
    {
        return BankProcessForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return BankProcessesTable::configure($table);
    }

    public static function getEloquentQuery(): Builder
    {
        $query = parent::getEloquentQuery()->with(['salesCase.consumer', 'salesCase.project', 'salesCase.unit', 'bank', 'documentSubmission']);
        $user = User::current();

        return $user?->isBranchScoped() ? $query->whereHas('salesCase', fn (Builder $query) => $query->where('branch_id', $user->branch_id)) : $query;
    }

    public static function getPages(): array
    {
        return ['index' => ListBankProcesses::route('/'), 'create' => CreateBankProcess::route('/create')];
    }
}
