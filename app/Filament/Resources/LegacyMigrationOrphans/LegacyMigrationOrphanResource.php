<?php

namespace App\Filament\Resources\LegacyMigrationOrphans;

use App\Filament\Resources\LegacyMigrationOrphans\Pages\ListLegacyMigrationOrphans;
use App\Filament\Resources\LegacyMigrationOrphans\Pages\ViewLegacyMigrationOrphan;
use App\Filament\Resources\LegacyMigrationOrphans\Schemas\LegacyMigrationOrphanInfolist;
use App\Filament\Resources\LegacyMigrationOrphans\Tables\LegacyMigrationOrphansTable;
use App\Models\LegacyMigrationOrphan;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use UnitEnum;

class LegacyMigrationOrphanResource extends Resource
{
    protected static ?string $model = LegacyMigrationOrphan::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedArchiveBoxXMark;

    protected static string|UnitEnum|null $navigationGroup = 'Legacy Migration';

    protected static ?string $navigationLabel = 'Migration Orphans';

    public static function infolist(Schema $schema): Schema
    {
        return LegacyMigrationOrphanInfolist::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return LegacyMigrationOrphansTable::configure($table);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListLegacyMigrationOrphans::route('/'),
            'view' => ViewLegacyMigrationOrphan::route('/{record}'),
        ];
    }
}
