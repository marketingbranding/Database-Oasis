<?php

namespace App\Filament\Resources\SalesCases\RelationManagers;

use App\Actions\CreateBastAction;
use App\Models\BastRecord;
use App\Models\SalesCase;
use App\Models\User;
use Filament\Actions\Action;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class BastRelationManager extends RelationManager
{
    protected static string $relationship = 'bast';

    public function table(Table $table): Table
    {
        return $table->columns([
            TextColumn::make('akad.document_number')->label('Akad'), TextColumn::make('bast_number')->label('Nomor BAST')->placeholder('-'),
            TextColumn::make('bast_date')->date(), TextColumn::make('status')->badge(),
        ])->headerActions([
            Action::make('createBast')->label('Create BAST')->visible(fn (RelationManager $livewire): bool => $livewire->getOwnerRecord() instanceof SalesCase && $livewire->getOwnerRecord()->akad()->exists() && ! $livewire->getOwnerRecord()->bast()->exists() && (User::current()?->can('create', BastRecord::class) ?? false))
                ->form([TextInput::make('bast_number')->label('Nomor BAST'), DatePicker::make('bast_date')->default(now())->required(), Textarea::make('notes')])
                ->action(function (array $data, RelationManager $livewire): void {
                    $case = $livewire->getOwnerRecord();
                    if (! $case instanceof SalesCase) {
                        abort(404);
                    }
                    $akad = $case->akad()->firstOrFail();
                    app(CreateBastAction::class)->handle(User::current() ?? abort(403), ['sales_case_id' => $case->id, 'akad_id' => $akad->id, ...$data]);
                }),
        ]);
    }
}
