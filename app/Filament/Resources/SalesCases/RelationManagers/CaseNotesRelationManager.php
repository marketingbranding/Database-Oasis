<?php

namespace App\Filament\Resources\SalesCases\RelationManagers;

use App\Actions\CreateCaseNoteAction;
use App\Models\CaseNote;
use App\Models\User;
use Filament\Actions\Action;
use Filament\Forms\Components\Textarea;
use Filament\Notifications\Notification;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class CaseNotesRelationManager extends RelationManager
{
    protected static string $relationship = 'caseNotes';

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('note')
            ->columns([
                TextColumn::make('note')->label('Catatan')->wrap(),
                TextColumn::make('createdBy.name')->label('Oleh')->placeholder('-'),
                TextColumn::make('created_at')->label('Dibuat')->dateTime()->sortable(),
            ])
            ->defaultSort('created_at', 'desc')
            ->headerActions([
                Action::make('addCaseNote')->label('Add Catatan')->icon(Heroicon::OutlinedPlusCircle)
                    ->visible(fn (): bool => User::current()?->can('create', CaseNote::class) ?? false)
                    ->form([Textarea::make('note')->label('Catatan')->required()->maxLength(2000)])
                    ->action(function (array $data, RelationManager $livewire): void {
                        app(CreateCaseNoteAction::class)->handle(User::current() ?? abort(403), [
                            'sales_case_id' => $livewire->getOwnerRecord()->getKey(),
                            'note' => $data['note'],
                        ]);
                        Notification::make()->title('Catatan ditambahkan')->success()->send();
                    }),
            ]);
    }
}
