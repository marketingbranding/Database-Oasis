<?php

namespace App\Filament\Resources\DeveloperPpjbs\Schemas;

use App\Models\SalesCase;
use App\Models\User;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Schemas\Schema;

class DeveloperPpjbForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema->components([
            Select::make('sales_case_id')->label('Transaksi Penjualan')->options(fn (): array => SalesCase::pickableActiveCases(User::current())->get()->mapWithKeys(fn (SalesCase $case): array => [$case->id => "{$case->consumer?->name} — {$case->unit?->unit_code}"])->all())->searchable()->required(),
            DatePicker::make('document_date')->label('Tanggal PPJB')->default(now())->required(),
            Textarea::make('notes')->label('Catatan'),
        ]);
    }
}
