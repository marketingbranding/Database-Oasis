<?php

namespace App\Filament\Resources\LegacyMigrationBatches;

use App\Filament\Resources\LegacyMigrationBatches\Pages\ListLegacyMigrationBatches;
use App\Filament\Resources\LegacyMigrationBatches\Pages\ViewLegacyMigrationBatch;
use App\Filament\Resources\LegacyMigrationBatches\Schemas\LegacyMigrationBatchInfolist;
use App\Filament\Resources\LegacyMigrationBatches\Tables\LegacyMigrationBatchesTable;
use App\Models\LegacyMigrationBatch;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use UnitEnum;

class LegacyMigrationBatchResource extends Resource
{
    protected static ?string $model = LegacyMigrationBatch::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedArchiveBox;

    protected static string|UnitEnum|null $navigationGroup = 'Legacy Migration';

    protected static ?string $navigationLabel = 'Migration Batches';

    public static function infolist(Schema $schema): Schema
    {
        return LegacyMigrationBatchInfolist::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return LegacyMigrationBatchesTable::configure($table);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListLegacyMigrationBatches::route('/'),
            'view' => ViewLegacyMigrationBatch::route('/{record}'),
        ];
    }
}
