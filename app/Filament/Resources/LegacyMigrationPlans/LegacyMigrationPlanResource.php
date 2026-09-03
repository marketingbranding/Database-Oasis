<?php

namespace App\Filament\Resources\LegacyMigrationPlans;

use App\Filament\Resources\LegacyMigrationPlans\Pages\ListLegacyMigrationPlans;
use App\Filament\Resources\LegacyMigrationPlans\Pages\ViewLegacyMigrationPlan;
use App\Filament\Resources\LegacyMigrationPlans\Schemas\LegacyMigrationPlanInfolist;
use App\Filament\Resources\LegacyMigrationPlans\Tables\LegacyMigrationPlansTable;
use App\Models\LegacyMigrationPlan;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use UnitEnum;

class LegacyMigrationPlanResource extends Resource
{
    protected static ?string $model = LegacyMigrationPlan::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedClipboardDocumentCheck;

    protected static string|UnitEnum|null $navigationGroup = 'Legacy Migration';

    protected static ?string $navigationLabel = 'Migration Plans';

    public static function infolist(Schema $schema): Schema
    {
        return LegacyMigrationPlanInfolist::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return LegacyMigrationPlansTable::configure($table);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListLegacyMigrationPlans::route('/'),
            'view' => ViewLegacyMigrationPlan::route('/{record}'),
        ];
    }
}
