<?php

namespace App\Filament\Resources\SalesCases\RelationManagers;

use App\Actions\CreateAkadAction;
use App\Models\AkadRecord;
use App\Models\SalesCase;
use App\Models\User;
use Filament\Actions\Action;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class AkadRelationManager extends RelationManager
{
    protected static string $relationship = 'akad';

    public function table(Table $table): Table
    {
        return $table->columns([
            TextColumn::make('developerPpjb.document_number')->label('PPJB'), TextColumn::make('document_number')->label('Nomor Akad')->placeholder('-'),
            TextColumn::make('akad_date')->date(), TextColumn::make('akad_quality')->label('Kualitas')->placeholder('-'),
        ])->headerActions([
            Action::make('createAkad')->label('Create Akad')->visible(fn (RelationManager $livewire): bool => $livewire->getOwnerRecord() instanceof SalesCase && ! $livewire->getOwnerRecord()->akad()->exists() && (User::current()?->can('create', AkadRecord::class) ?? false))
                ->form([TextInput::make('document_number')->label('Nomor Akad'), DatePicker::make('akad_date')->default(now())->required(), TextInput::make('akad_quality'), Textarea::make('notes')])
                ->action(function (array $data, RelationManager $livewire): void {
                    $case = $livewire->getOwnerRecord();
                    if (! $case instanceof SalesCase) {
                        abort(404);
                    }
                    $ppjb = $case->activeDeveloperPpjb()->firstOrFail();
                    app(CreateAkadAction::class)->handle(User::current() ?? abort(403), ['sales_case_id' => $case->id, 'developer_ppjb_id' => $ppjb->id, ...$data]);
                }),
        ]);
    }
}
