<?php

namespace App\Filament\Resources\SalesCases\RelationManagers;

use App\Actions\RecordBankResponseAction;
use App\BankResponseType;
use App\FinancingType;
use App\Models\BankProcess;
use App\Models\DocumentSubmission;
use App\Models\SalesCase;
use App\Models\User;
use App\SalesCaseStatus;
use Filament\Actions\Action;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class BankProcessesRelationManager extends RelationManager
{
    protected static string $relationship = 'bankProcesses';

    public function table(Table $table): Table
    {
        return $table->columns([
            TextColumn::make('documentSubmission.sequence')->label('Submission #'),
            TextColumn::make('bank.name')->label('Bank'),
            TextColumn::make('response_type')->label('Response')->badge(),
            TextColumn::make('response_date')->label('Tanggal')->date(),
            TextColumn::make('sp3k_number')->label('SP3K')->placeholder('-'),
            IconColumn::make('is_authoritative')->label('Authoritative')->boolean(),
        ])->headerActions([$this->recordResponseAction()])->defaultSort('response_date', 'desc');
    }

    private function recordResponseAction(): Action
    {
        return Action::make('recordBankResponse')->label('Record Bank Response')->icon(Heroicon::OutlinedPlusCircle)
            ->visible(function (RelationManager $livewire): bool {
                $case = $livewire->getOwnerRecord();

                return $case instanceof SalesCase && $case->case_status === SalesCaseStatus::Active
                    && $case->financing_type === FinancingType::KprSubsidi
                    && (User::current()?->can('create', BankProcess::class) ?? false);
            })->form([
                Select::make('document_submission_id')->label('Submission')->options(function (RelationManager $livewire): array {
                    $case = $livewire->getOwnerRecord();
                    if (! $case instanceof SalesCase) {
                        return [];
                    }

                    return $case->documentSubmissions()->with('bank')->get()
                        ->mapWithKeys(fn (DocumentSubmission $submission): array => [$submission->id => "#{$submission->sequence} — {$submission->bank?->name}"])->all();
                })->required(),
                Select::make('response_type')->label('Response')->options(BankResponseType::class)->live()->required(),
                DatePicker::make('response_date')->label('Tanggal')->default(now())->required(),
                TextInput::make('sp3k_number')->label('Nomor SP3K')->required(fn (Get $get): bool => $get('response_type') === BankResponseType::Approved->value),
                DatePicker::make('sp3k_date')->label('Tanggal SP3K')->required(fn (Get $get): bool => $get('response_type') === BankResponseType::Approved->value),
                TextInput::make('credit_limit')->numeric(), TextInput::make('tenor')->numeric(), Textarea::make('notes'),
            ])->action(function (array $data, RelationManager $livewire): void {
                $case = $livewire->getOwnerRecord();
                $submission = DocumentSubmission::findOrFail($data['document_submission_id']);
                app(RecordBankResponseAction::class)->handle(User::current() ?? abort(403), [
                    'sales_case_id' => $case->getKey(), 'bank_id' => $submission->bank_id, ...$data,
                ]);
                Notification::make()->title('Bank response dicatat')->success()->send();
            });
    }
}
