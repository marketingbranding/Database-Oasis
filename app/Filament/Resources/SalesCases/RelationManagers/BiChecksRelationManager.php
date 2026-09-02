<?php

namespace App\Filament\Resources\SalesCases\RelationManagers;

use App\Actions\RecordBiCheckAction;
use App\BiCheckResult;
use App\Models\BiCheck;
use App\Models\SalesCase;
use App\Models\User;
use App\SalesCaseStatus;
use Filament\Actions\Action;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Notifications\Notification;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class BiChecksRelationManager extends RelationManager
{
    protected static string $relationship = 'biChecks';

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('check_date')
            ->columns([
                TextColumn::make('check_date')
                    ->label('Tanggal')
                    ->date()
                    ->sortable(),
                TextColumn::make('result')
                    ->label('Hasil')
                    ->badge()
                    ->formatStateUsing(fn (BiCheckResult $state): string => $state->getLabel()),
                TextColumn::make('description')
                    ->label('Keterangan')
                    ->limit(50)
                    ->placeholder('-'),
                TextColumn::make('createdBy.name')
                    ->label('Dicatat Oleh')
                    ->placeholder('-'),
                TextColumn::make('created_at')
                    ->label('Dibuat')
                    ->dateTime(),
            ])
            ->defaultSort('check_date', 'desc')
            ->headerActions([
                Action::make('recordBiCheck')
                    ->label('Add BI Check')
                    ->icon(Heroicon::OutlinedPlusCircle)
                    ->form([
                        DatePicker::make('check_date')
                            ->label('Tanggal Cek')
                            ->default(now())
                            ->required(),
                        Select::make('result')
                            ->label('Hasil')
                            ->options(BiCheckResult::class)
                            ->required(),
                        Textarea::make('description')
                            ->label('Keterangan')
                            ->maxLength(1000),
                    ])
                    ->visible(function (RelationManager $livewire): bool {
                        $case = $livewire->getOwnerRecord();

                        return $case instanceof SalesCase
                            && $case->case_status === SalesCaseStatus::Active
                            && (User::current()?->can('create', BiCheck::class) ?? false);
                    })
                    ->action(function (array $data, RelationManager $livewire): void {
                        $case = $livewire->getOwnerRecord();
                        $user = User::current() ?? abort(403);

                        app(RecordBiCheckAction::class)->handle($user, [
                            'sales_case_id' => $case->getKey(),
                            'check_date' => $data['check_date'],
                            'result' => $data['result'],
                            'description' => $data['description'] ?? null,
                        ]);

                        Notification::make()
                            ->title('BI check dicatat')
                            ->success()
                            ->send();
                    }),
            ]);
    }
}
