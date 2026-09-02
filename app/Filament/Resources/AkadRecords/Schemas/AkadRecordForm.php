<?php

namespace App\Filament\Resources\AkadRecords\Schemas;

use App\Models\AkadRecord;
use App\Models\DeveloperPpjb;
use App\Models\SalesCase;
use App\Models\User;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Schema;

class AkadRecordForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema->components([
            Select::make('sales_case_id')->label('Sales Case')->options(fn (): array => SalesCase::pickableActiveCases(User::current())->get()->mapWithKeys(fn (SalesCase $case): array => [$case->id => "{$case->consumer?->name} — {$case->unit?->unit_code}"])->all())->live()->required(),
            Select::make('developer_ppjb_id')->label('PPJB Developer')->options(fn (Get $get): array => DeveloperPpjb::query()->where('sales_case_id', $get('sales_case_id'))->where('status', 'ACTIVE')->pluck('document_number', 'id')->all())->required(),
            TextInput::make('document_number')->label('Nomor Akad')->live(onBlur: true)->helperText(fn (Get $get): ?string => self::warning($get('document_number'))),
            DatePicker::make('akad_date')->label('Tanggal Akad')->default(now())->required(),
            TextInput::make('akad_quality')->label('Kualitas Akad'),
            Textarea::make('notes')->label('Catatan'),
        ]);
    }

    private static function warning(mixed $number): ?string
    {
        if (blank($number)) {
            return null;
        }
        $count = AkadRecord::query()->where('document_number', $number)->count();

        return $count ? "Peringatan: nomor Akad sudah ada pada {$count} dokumen." : null;
    }
}
