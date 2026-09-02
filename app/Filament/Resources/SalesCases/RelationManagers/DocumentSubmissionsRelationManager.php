<?php

namespace App\Filament\Resources\SalesCases\RelationManagers;

use App\Actions\CreateDocumentSubmissionAction;
use App\FinancingType;
use App\Models\Bank;
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
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class DocumentSubmissionsRelationManager extends RelationManager
{
    protected static string $relationship = 'documentSubmissions';

    public function table(Table $table): Table
    {
        return $table->columns([
            TextColumn::make('sequence')->label('#'),
            TextColumn::make('bank.name')->label('Bank'),
            TextColumn::make('submission_date')->label('Tanggal')->date(),
            TextColumn::make('status')->badge(),
            TextColumn::make('latestBankProcess.response_type')->label('Response Terakhir')->badge()->placeholder('-'),
        ])->headerActions([
            Action::make('addSubmission')->label('Add Pemberkasan / Submit Bank')->icon(Heroicon::OutlinedPlusCircle)
                ->visible(function (RelationManager $livewire): bool {
                    $case = $livewire->getOwnerRecord();

                    return $case instanceof SalesCase && $case->case_status === SalesCaseStatus::Active
                        && $case->financing_type === FinancingType::KprSubsidi
                        && (User::current()?->can('create', DocumentSubmission::class) ?? false);
                })->form([
                    Select::make('bank_id')->label('Bank')->options(fn (): array => Bank::query()->where('is_active', true)->orderBy('name')->pluck('name', 'id')->all())->required(),
                    DatePicker::make('submission_date')->label('Tanggal')->default(now())->required(),
                    TextInput::make('bank_branch')->label('Cabang Bank'),
                    Textarea::make('notes')->label('Catatan'),
                ])->action(function (array $data, RelationManager $livewire): void {
                    $user = User::current() ?? abort(403);
                    app(CreateDocumentSubmissionAction::class)->handle($user, ['sales_case_id' => $livewire->getOwnerRecord()->getKey(), ...$data]);
                    Notification::make()->title('Pemberkasan dibuat')->success()->send();
                }),
        ])->defaultSort('sequence', 'desc');
    }
}
