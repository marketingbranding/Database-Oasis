<?php

namespace App\Filament\Resources\Consumers\Schemas;

use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class ConsumerForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Identitas')
                    ->components([
                        TextInput::make('nik')
                            ->label('NIK')
                            ->required()
                            ->rule('digits:16')
                            ->maxLength(16)
                            ->unique(ignoreRecord: true),
                        TextInput::make('name')
                            ->label('Nama')
                            ->required()
                            ->maxLength(255),
                        TextInput::make('phone')
                            ->label('Telepon')
                            ->maxLength(255),
                        TextInput::make('email')
                            ->label('Email')
                            ->email()
                            ->maxLength(255),
                    ]),
                Section::make('Data Pribadi')
                    ->components([
                        Select::make('gender')
                            ->label('Jenis Kelamin')
                            ->options([
                                'L' => 'Laki-laki',
                                'P' => 'Perempuan',
                            ]),
                        TextInput::make('birth_place')
                            ->label('Tempat Lahir')
                            ->maxLength(255),
                        DatePicker::make('birth_date')
                            ->label('Tanggal Lahir'),
                        Select::make('marital_status')
                            ->label('Status Pernikahan')
                            ->options([
                                'Belum Menikah' => 'Belum Menikah',
                                'Menikah' => 'Menikah',
                                'Cerai' => 'Cerai',
                            ]),
                        Textarea::make('address')
                            ->label('Alamat')
                            ->columnSpanFull(),
                    ]),
                Section::make('Pekerjaan')
                    ->components([
                        TextInput::make('occupation')
                            ->label('Pekerjaan')
                            ->maxLength(255),
                        TextInput::make('company')
                            ->label('Perusahaan')
                            ->maxLength(255),
                        TextInput::make('monthly_income')
                            ->label('Penghasilan per Bulan')
                            ->numeric()
                            ->minValue(0),
                        TextInput::make('npwp')
                            ->label('NPWP')
                            ->maxLength(20),
                    ]),
                Section::make('Catatan')
                    ->components([
                        Textarea::make('notes')
                            ->label('Catatan')
                            ->columnSpanFull(),
                    ]),
            ]);
    }
}
