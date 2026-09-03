<?php

namespace App\Filament\Resources\LegacyMigrationCandidates;

use App\Filament\Resources\LegacyMigrationCandidates\Pages\ListLegacyMigrationCandidates;
use App\Filament\Resources\LegacyMigrationCandidates\Pages\ViewLegacyMigrationCandidate;
use App\Filament\Resources\LegacyMigrationCandidates\Schemas\LegacyMigrationCandidateInfolist;
use App\Filament\Resources\LegacyMigrationCandidates\Tables\LegacyMigrationCandidatesTable;
use App\Models\LegacyMigrationCandidate;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use UnitEnum;

class LegacyMigrationCandidateResource extends Resource
{
    protected static ?string $model = LegacyMigrationCandidate::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedQueueList;

    protected static string|UnitEnum|null $navigationGroup = 'Legacy Migration';

    protected static ?string $navigationLabel = 'Migration Candidates';

    public static function infolist(Schema $schema): Schema
    {
        return LegacyMigrationCandidateInfolist::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return LegacyMigrationCandidatesTable::configure($table);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListLegacyMigrationCandidates::route('/'),
            'view' => ViewLegacyMigrationCandidate::route('/{record}'),
        ];
    }
}
