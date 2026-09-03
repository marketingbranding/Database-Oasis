<?php

namespace App\Filament\Resources\LegacyMigrationBatches\Schemas;

use App\Models\LegacyMigrationBatch;
use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class LegacyMigrationBatchInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema->components([
            Section::make('Batch')->schema([
                TextEntry::make('source_filename')->label('Source'),
                TextEntry::make('source_fingerprint')->label('Source Fingerprint')->copyable(),
                TextEntry::make('audit_fingerprint')->label('Audit Fingerprint')->copyable(),
                TextEntry::make('status')->label('Status')->badge(),
                TextEntry::make('createdBy.name')->label('Dibuat Oleh'),
                TextEntry::make('created_at')->label('Dibuat')->dateTime(),
                TextEntry::make('completed_at')->label('Selesai')->dateTime()->placeholder('-'),
            ]),
            Section::make('Readiness')->schema([
                TextEntry::make('candidates_count')->label('Candidates')
                    ->state(fn (LegacyMigrationBatch $record): int => $record->candidates()->count()),
                TextEntry::make('auto_count')->label('AUTO')
                    ->state(fn (LegacyMigrationBatch $record): int => $record->candidates()->where('readiness', 'AUTO')->count()),
                TextEntry::make('review_count')->label('REVIEW')
                    ->state(fn (LegacyMigrationBatch $record): int => $record->candidates()->where('readiness', 'REVIEW')->count()),
                TextEntry::make('blocked_count')->label('BLOCKED')
                    ->state(fn (LegacyMigrationBatch $record): int => $record->candidates()->where('readiness', 'BLOCKED')->count()),
            ]),
        ]);
    }
}
