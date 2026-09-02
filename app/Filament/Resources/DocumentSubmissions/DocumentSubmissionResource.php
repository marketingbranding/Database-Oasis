<?php

namespace App\Filament\Resources\DocumentSubmissions;

use App\Filament\Resources\DocumentSubmissions\Pages\CreateDocumentSubmission;
use App\Filament\Resources\DocumentSubmissions\Pages\ListDocumentSubmissions;
use App\Filament\Resources\DocumentSubmissions\Schemas\DocumentSubmissionForm;
use App\Filament\Resources\DocumentSubmissions\Tables\DocumentSubmissionsTable;
use App\Models\DocumentSubmission;
use App\Models\User;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use UnitEnum;

class DocumentSubmissionResource extends Resource
{
    protected static ?string $model = DocumentSubmission::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedFolderArrowDown;

    protected static string|UnitEnum|null $navigationGroup = 'Proses Penjualan';

    protected static ?string $navigationLabel = 'Pemberkasan';

    protected static ?string $modelLabel = 'Pemberkasan';

    public static function form(Schema $schema): Schema
    {
        return DocumentSubmissionForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return DocumentSubmissionsTable::configure($table);
    }

    public static function getEloquentQuery(): Builder
    {
        $query = parent::getEloquentQuery()->with(['salesCase.consumer', 'salesCase.project', 'salesCase.unit', 'bank', 'latestBankProcess']);
        $user = User::current();

        return $user?->isBranchScoped() ? $query->whereHas('salesCase', fn (Builder $query) => $query->where('branch_id', $user->branch_id)) : $query;
    }

    public static function getPages(): array
    {
        return ['index' => ListDocumentSubmissions::route('/'), 'create' => CreateDocumentSubmission::route('/create')];
    }
}
