<?php

namespace App\Filament\Resources\BankProcesses\Schemas;

use App\BankResponseType;
use App\Models\BankProcess;
use App\Models\DocumentSubmission;
use App\Models\User;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Schema;

class BankProcessForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema->components([
            Select::make('document_submission_id')->label('Submission')->options(fn (): array => self::submissionOptions())->searchable()->required(),
            Select::make('response_type')->label('Response')->options(BankResponseType::class)->live()->required(),
            DatePicker::make('response_date')->label('Tanggal Response')->default(now())->required(),
            TextInput::make('sp3k_number')->label('Nomor SP3K')->live(onBlur: true)
                ->required(fn (Get $get): bool => $get('response_type') === BankResponseType::Approved->value)
                ->helperText(function (Get $get): ?string {
                    $number = $get('sp3k_number');
                    if (blank($number)) {
                        return null;
                    }

                    $count = BankProcess::query()->where('sp3k_number', $number)->count();

                    return $count > 0 ? "Peringatan: nomor SP3K sudah tercatat pada {$count} proses lain." : null;
                }),
            DatePicker::make('sp3k_date')->label('Tanggal SP3K')->required(fn (Get $get): bool => $get('response_type') === BankResponseType::Approved->value),
            TextInput::make('credit_limit')->label('Plafon Kredit')->numeric()->minValue(0),
            TextInput::make('tenor')->label('Tenor')->numeric()->minValue(1),
            Textarea::make('notes')->label('Catatan')->maxLength(1000),
        ]);
    }

    /** @return array<string, string> */
    private static function submissionOptions(): array
    {
        $user = User::current();

        return DocumentSubmission::query()->with(['salesCase.consumer', 'salesCase.unit', 'bank'])
            ->when($user?->isBranchScoped(), fn ($query) => $query->whereHas('salesCase', fn ($query) => $query->where('branch_id', $user->branch_id)))
            ->latest()->limit(100)->get()->mapWithKeys(fn (DocumentSubmission $submission): array => [
                $submission->id => "#{$submission->sequence} {$submission->salesCase?->consumer?->name} — {$submission->bank?->name}",
            ])->all();
    }
}
