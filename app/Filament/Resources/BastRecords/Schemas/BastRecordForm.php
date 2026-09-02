<?php

namespace App\Filament\Resources\BastRecords\Schemas;

use App\Models\AkadRecord;
use App\Models\BastRecord;
use App\Models\SalesCase;
use App\Models\User;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Schema;

class BastRecordForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema->components([
            Select::make('sales_case_id')->label('Sales Case')->options(fn (): array => SalesCase::pickableActiveCases(User::current())->get()->mapWithKeys(fn (SalesCase $case): array => [$case->id => "{$case->consumer?->name} — {$case->unit?->unit_code}"])->all())->live()->required(),
            Select::make('akad_id')->label('Akad')->options(fn (Get $get): array => AkadRecord::query()->where('sales_case_id', $get('sales_case_id'))->pluck('document_number', 'id')->all())->required(),
            TextInput::make('bast_number')->label('Nomor BAST')->live(onBlur: true)->helperText(fn (Get $get): ?string => self::warning($get('bast_number'))),
            DatePicker::make('bast_date')->label('Tanggal BAST')->default(now())->required(),
            Textarea::make('notes')->label('Catatan'),
        ]);
    }

    private static function warning(mixed $number): ?string
    {
        if (blank($number)) {
            return null;
        }
        $count = BastRecord::query()->where('bast_number', $number)->count();

        return $count ? "Peringatan: nomor BAST sudah ada pada {$count} dokumen." : null;
    }
}
