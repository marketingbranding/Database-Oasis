<?php

namespace App\Filament\Resources\SalesCases\RelationManagers;

use App\Actions\CancelPsjbAction;
use App\Actions\CreatePsjbAction;
use App\Actions\ReissuePsjbAction;
use App\Models\Psjb;
use App\Models\SalesCase;
use App\Models\User;
use App\PsjbStatus;
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

class PsjbsRelationManager extends RelationManager
{
    protected static string $relationship = 'psjbs';

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('document_number')
            ->columns([
                TextColumn::make('psjb_date')
                    ->label('Tanggal PSJB')
                    ->date()
                    ->sortable(),
                TextColumn::make('document_number')
                    ->label('Nomor Dokumen')
                    ->placeholder('-'),
                TextColumn::make('status')
                    ->label('Status')
                    ->badge()
                    ->formatStateUsing(fn (PsjbStatus $state): string => $state->getLabel()),
                TextColumn::make('coordinator.name')
                    ->label('Koordinator')
                    ->placeholder('-'),
                TextColumn::make('createdBy.name')
                    ->label('Dibuat Oleh')
                    ->placeholder('-'),
                TextColumn::make('updated_at')
                    ->label('Diubah')
                    ->dateTime(),
            ])
            ->defaultSort('psjb_date', 'desc')
            ->headerActions([
                $this->createPsjbAction(),
            ])
            ->recordActions([
                $this->reissuePsjbAction(),
                $this->cancelPsjbAction(),
            ]);
    }

    private function createPsjbAction(): Action
    {
        return Action::make('createPsjb')
            ->label('Add PSJB')
            ->icon(Heroicon::OutlinedPlusCircle)
            ->form($this->psjbFormFields())
            ->visible(function (RelationManager $livewire): bool {
                $case = $livewire->getOwnerRecord();

                return $case instanceof SalesCase
                    && $case->case_status === SalesCaseStatus::Active
                    && (User::current()?->can('create', Psjb::class) ?? false);
            })
            ->action(function (array $data, RelationManager $livewire): void {
                $case = $livewire->getOwnerRecord();
                $user = User::current() ?? abort(403);

                app(CreatePsjbAction::class)->handle($user, [
                    'sales_case_id' => $case->getKey(),
                    ...$data,
                ]);

                Notification::make()
                    ->title('PSJB dibuat')
                    ->success()
                    ->send();
            });
    }

    private function reissuePsjbAction(): Action
    {
        return Action::make('reissuePsjb')
            ->label('Reissue PSJB')
            ->icon(Heroicon::OutlinedArrowPath)
            ->color('warning')
            ->form($this->psjbFormFields())
            ->visible(function (Psjb $record, RelationManager $livewire): bool {
                $case = $livewire->getOwnerRecord();

                return $record->status === PsjbStatus::Active
                    && $case instanceof SalesCase
                    && $case->case_status === SalesCaseStatus::Active
                    && (User::current()?->can('create', Psjb::class) ?? false);
            })
            ->action(function (array $data, RelationManager $livewire): void {
                $case = $livewire->getOwnerRecord();

                if (! $case instanceof SalesCase) {
                    abort(404);
                }

                $user = User::current() ?? abort(403);

                app(ReissuePsjbAction::class)->handle($user, $case, $data);

                Notification::make()
                    ->title('PSJB di-reissue')
                    ->body('PSJB lama ditandai SUPERSEDED; PSJB baru dibuat.')
                    ->success()
                    ->send();
            });
    }

    private function cancelPsjbAction(): Action
    {
        return Action::make('cancelPsjb')
            ->label('Cancel PSJB')
            ->icon(Heroicon::OutlinedMinusCircle)
            ->color('danger')
            ->requiresConfirmation()
            ->modalHeading('Batalkan PSJB aktif?')
            ->visible(function (Psjb $record): bool {
                return $record->status === PsjbStatus::Active
                    && (User::current()?->can('create', Psjb::class) ?? false);
            })
            ->action(function (Psjb $record): void {
                $user = User::current() ?? abort(403);

                app(CancelPsjbAction::class)->handle($user, $record);

                Notification::make()
                    ->title('PSJB dibatalkan')
                    ->body('Sales case kembali ke tahap PSJB dan tetap aktif.')
                    ->success()
                    ->send();
            });
    }

    /**
     * @return array<int, mixed>
     */
    private function psjbFormFields(): array
    {
        return [
            DatePicker::make('psjb_date')
                ->label('Tanggal PSJB')
                ->default(now())
                ->required(),
            TextInput::make('document_number')
                ->label('Nomor Dokumen')
                ->maxLength(255),
            Select::make('coordinator_id')
                ->label('Koordinator')
                ->options(fn (): array => User::query()->where('is_active', true)->orderBy('name')->limit(50)->pluck('name', 'id')->all())
                ->searchable(),
            Textarea::make('notes')
                ->label('Catatan')
                ->maxLength(1000),
        ];
    }
}
