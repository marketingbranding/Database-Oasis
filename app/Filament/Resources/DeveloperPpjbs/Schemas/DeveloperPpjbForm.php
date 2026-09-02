<?php

namespace App\Filament\Resources\DeveloperPpjbs\Schemas;

use App\Models\DeveloperPpjb;
use App\Models\SalesCase;
use App\Models\User;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Schema;

class DeveloperPpjbForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema->components([
            Select::make('sales_case_id')->label('Sales Case')->options(fn (): array => SalesCase::pickableActiveCases(User::current())->get()->mapWithKeys(fn (SalesCase $case): array => [$case->id => "{$case->consumer?->name} — {$case->unit?->unit_code}"])->all())->searchable()->required(),
            TextInput::make('document_number')->label('Nomor PPJB')->live(onBlur: true)->helperText(fn (Get $get): ?string => self::warning($get('document_number'))),
            DatePicker::make('document_date')->label('Tanggal PPJB')->default(now())->required(),
            Textarea::make('notes')->label('Catatan'),
        ]);
    }

    private static function warning(mixed $number): ?string
    {
        if (blank($number)) {
            return null;
        }
        $count = DeveloperPpjb::query()->where('document_number', $number)->count();

        return $count ? "Peringatan: nomor PPJB sudah ada pada {$count} dokumen." : null;
    }
}
