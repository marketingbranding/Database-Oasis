<?php

namespace App\Filament\Resources\AkadTargets;

use App\Filament\Resources\AkadTargets\Pages\CreateAkadTarget;
use App\Filament\Resources\AkadTargets\Pages\EditAkadTarget;
use App\Filament\Resources\AkadTargets\Pages\ListAkadTargets;
use App\Filament\Resources\AkadTargets\Schemas\AkadTargetForm;
use App\Filament\Resources\AkadTargets\Tables\AkadTargetsTable;
use App\Models\AkadTarget;
use App\Models\User;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use UnitEnum;

class AkadTargetResource extends Resource
{
    protected static ?string $model = AkadTarget::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedFlag;

    protected static string|UnitEnum|null $navigationGroup = 'Monitoring';

    protected static ?string $navigationLabel = 'Target Akad';

    protected static ?string $modelLabel = 'Target Akad';

    protected static ?string $pluralModelLabel = 'Target Akad';

    protected static ?int $navigationSort = 4;

    public static function form(Schema $schema): Schema
    {
        return AkadTargetForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return AkadTargetsTable::configure($table);
    }

    public static function getEloquentQuery(): Builder
    {
        $query = parent::getEloquentQuery()->with(['branch', 'project']);
        $user = User::current();

        return $query->when($user?->isBranchScoped(), fn (Builder $query) => $query->where('branch_id', $user->branch_id));
    }

    public static function getPages(): array
    {
        return [
            'index' => ListAkadTargets::route('/'),
            'create' => CreateAkadTarget::route('/create'),
            'edit' => EditAkadTarget::route('/{record}/edit'),
        ];
    }
}
