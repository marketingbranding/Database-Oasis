<?php

namespace App\Filament\Resources\SalesCases\RelationManagers;

use App\Actions\CancelDeveloperPpjbAction;
use App\Actions\CreateDeveloperPpjbAction;
use App\Actions\ReissueDeveloperPpjbAction;
use App\DeveloperPpjbStatus;
use App\Models\DeveloperPpjb;
use App\Models\SalesCase;
use App\Models\User;
use App\SalesCaseStatus;
use Filament\Actions\Action;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class DeveloperPpjbsRelationManager extends RelationManager
{
    protected static string $relationship = 'developerPpjbs';

    public function table(Table $table): Table
    {
        return $table->columns([
            TextColumn::make('document_number')->label('Nomor PPJB')->placeholder('-'), TextColumn::make('document_date')->date(),
            TextColumn::make('status')->badge(), TextColumn::make('bankProcess.sp3k_number')->label('SP3K')->placeholder('-'),
        ])->headerActions([$this->createAction()])->recordActions([$this->reissueAction(), $this->cancelAction()])->defaultSort('document_date', 'desc');
    }

    /** @return array<int, mixed> */
    private function fields(): array
    {
        return [TextInput::make('document_number')->label('Nomor PPJB'), DatePicker::make('document_date')->default(now())->required(), Textarea::make('notes')];
    }

    private function createAction(): Action
    {
        return Action::make('createPpjbDeveloper')->label('Create PPJB')->form($this->fields())->visible(fn (RelationManager $livewire): bool => $livewire->getOwnerRecord() instanceof SalesCase && (User::current()?->can('create', DeveloperPpjb::class) ?? false))
            ->action(function (array $data, RelationManager $livewire): void {
                app(CreateDeveloperPpjbAction::class)->handle(User::current() ?? abort(403), ['sales_case_id' => $livewire->getOwnerRecord()->getKey(), ...$data]);
                Notification::make()->title('PPJB Developer dibuat')->success()->send();
            });
    }

    private function reissueAction(): Action
    {
        return Action::make('reissuePpjbDeveloper')->label('Reissue')->form($this->fields())->visible(fn (DeveloperPpjb $record): bool => $record->status === DeveloperPpjbStatus::Active && ! $record->salesCase->akad()->exists())
            ->action(function (array $data, RelationManager $livewire): void {
                $case = $livewire->getOwnerRecord();
                if (! $case instanceof SalesCase) {
                    abort(404);
                }
                app(ReissueDeveloperPpjbAction::class)->handle(User::current() ?? abort(403), $case, $data);
            });
    }

    private function cancelAction(): Action
    {
        return Action::make('cancelPpjbDeveloper')->label('Cancel')->color('danger')->requiresConfirmation()->visible(fn (DeveloperPpjb $record): bool => $record->status === DeveloperPpjbStatus::Active && $record->salesCase->case_status === SalesCaseStatus::Active && ! $record->salesCase->akad()->exists())
            ->action(fn (DeveloperPpjb $record) => app(CancelDeveloperPpjbAction::class)->handle(User::current() ?? abort(403), $record));
    }
}
