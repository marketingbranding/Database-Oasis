<?php

namespace App\Filament\Resources\LegacyMigrationOrphans\Schemas;

use App\Models\LegacyMigrationOrphan;
use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class LegacyMigrationOrphanInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema->components([
            Section::make('Orphan')->columns(3)->schema([
                TextEntry::make('source_sheet')->label('Sheet'),
                TextEntry::make('source_row')->label('Row'),
                TextEntry::make('orphan_code')->label('Code')->badge(),
                TextEntry::make('severity')->label('Severity')->badge(),
                TextEntry::make('status')->label('Status')->badge(),
                TextEntry::make('source_fingerprint')->label('Fingerprint')->copyable()->limit(12),
            ]),
            Section::make('Evidence')->schema([
                TextEntry::make('normalized_evidence')->label('Normalized Evidence')
                    ->state(fn (LegacyMigrationOrphan $record): string => json_encode($record->normalized_evidence, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE)),
                TextEntry::make('candidate_matches')->label('Candidate Matches')
                    ->state(fn (LegacyMigrationOrphan $record): string => json_encode($record->candidate_matches, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE)),
            ]),
        ]);
    }
}
