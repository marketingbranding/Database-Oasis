<?php

namespace App\Filament\Resources\LegacyMigrationCandidates\Schemas;

use App\Models\LegacyMigrationCandidate;
use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class LegacyMigrationCandidateInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema->components([
            Section::make('Kandidat')->columns(3)->schema([
                TextEntry::make('proposed_sales_case.name_normalized')->label('Konsumen'),
                TextEntry::make('proposed_unit.unit_original')->label('Unit'),
                TextEntry::make('confidence')->label('Confidence')->badge(),
                TextEntry::make('readiness')->label('Readiness')->badge(),
                TextEntry::make('lifecycle')->label('Lifecycle')->badge(),
                TextEntry::make('financing_type')->label('Financing')->badge(),
                TextEntry::make('source_fingerprint')->label('Fingerprint')->copyable()->limit(12),
            ]),
            Section::make('Consumer')->columns(2)->schema([
                TextEntry::make('proposed_consumer.name_original')->label('Nama'),
                TextEntry::make('proposed_consumer.nik_masked')->label('NIK (masked)'),
            ]),
            Section::make('Exceptions')->schema([
                TextEntry::make('exceptions_summary')->label('Exceptions')
                    ->state(fn (LegacyMigrationCandidate $record): string => $record->exceptions()
                        ->get()
                        ->map(fn ($exception): string => $exception->severity->value.' '.$exception->code.' '.$exception->message)
                        ->implode("\n")),
            ]),
        ]);
    }
}
