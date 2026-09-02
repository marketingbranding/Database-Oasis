<?php

namespace App\Filament\Resources\SalesCases;

use App\Filament\Resources\SalesCases\Pages\CreateSalesCase;
use App\Filament\Resources\SalesCases\Pages\EditSalesCase;
use App\Filament\Resources\SalesCases\Pages\ListSalesCases;
use App\Filament\Resources\SalesCases\Pages\ViewSalesCase;
use App\Filament\Resources\SalesCases\RelationManagers\AkadRelationManager;
use App\Filament\Resources\SalesCases\RelationManagers\BankProcessesRelationManager;
use App\Filament\Resources\SalesCases\RelationManagers\BastRelationManager;
use App\Filament\Resources\SalesCases\RelationManagers\BiChecksRelationManager;
use App\Filament\Resources\SalesCases\RelationManagers\CaseNotesRelationManager;
use App\Filament\Resources\SalesCases\RelationManagers\DeveloperPpjbsRelationManager;
use App\Filament\Resources\SalesCases\RelationManagers\DocumentSubmissionsRelationManager;
use App\Filament\Resources\SalesCases\RelationManagers\PsjbsRelationManager;
use App\Filament\Resources\SalesCases\Schemas\SalesCaseForm;
use App\Filament\Resources\SalesCases\Schemas\SalesCaseInfolist;
use App\Filament\Resources\SalesCases\Tables\SalesCasesTable;
use App\Models\SalesCase;
use App\Models\User;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use UnitEnum;

class SalesCaseResource extends Resource
{
    protected static ?string $model = SalesCase::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedClipboardDocumentList;

    protected static string|UnitEnum|null $navigationGroup = 'Operasional';

    protected static ?string $navigationLabel = 'Sales Cases';

    protected static ?string $modelLabel = 'Sales Case';

    protected static ?string $pluralModelLabel = 'Sales Cases';

    protected static ?int $navigationSort = 1;

    /**
     * Global search resolves to Sales Cases. Relation attributes produce OR
     * semantics inside each search term; document-number attributes are
     * intentionally non-unique, so one number may return several cases.
     *
     * @return array<array<string>>
     */
    public static function getGloballySearchableAttributes(): array
    {
        return [
            ['consumer.name'],
            ['consumer.nik'],
            ['consumer.phone'],
            ['unit.unit_code'],
            ['bankProcesses.sp3k_number'],
            ['psjbs.document_number'],
            ['developerPpjbs.document_number'],
            ['akad.document_number'],
            ['bast.bast_number'],
        ];
    }

    public static function getGlobalSearchResultTitle(Model $record): string
    {
        if (! $record instanceof SalesCase) {
            return (string) $record->getKey();
        }

        return sprintf('%s — %s', $record->consumer->name, $record->unit->unit_code);
    }

    /** @return array<string, string> */
    public static function getGlobalSearchResultDetails(Model $record): array
    {
        if (! $record instanceof SalesCase) {
            return [];
        }

        return [
            'Konsumen' => $record->consumer->name,
            'Proyek' => $record->project->name,
            'Unit' => $record->unit->unit_code,
            'Stage' => $record->current_stage->getLabel(),
            'Status' => $record->case_status->getLabel(),
        ];
    }

    public static function form(Schema $schema): Schema
    {
        return SalesCaseForm::configure($schema);
    }

    public static function infolist(Schema $schema): Schema
    {
        return SalesCaseInfolist::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return SalesCasesTable::configure($table);
    }

    public static function getEloquentQuery(): Builder
    {
        $query = parent::getEloquentQuery()
            ->with([
                'consumer', 'branch', 'project', 'unit',
                'activePsjb', 'activeDeveloperPpjb', 'akad', 'bast',
                'latestSubmission.bank', 'latestBankProcess.bank', 'currentApprovedBankProcess',
            ])
            // Aggregate stage-entry dates for aging without N+1 (see
            // SalesCase::daysInCurrentStage() for the documented hierarchy).
            ->withMax('biChecks', 'check_date')
            ->withMax('psjbs', 'psjb_date')
            ->withMax('documentSubmissions', 'submission_date')
            ->withMax('developerPpjbs', 'document_date')
            ->withMax('currentApprovedBankProcess', 'response_date')
            ->withMax('akad', 'akad_date')
            ->withMax('bast', 'bast_date');

        $user = User::current();
        if ($user?->isBranchScoped()) {
            $query->where('branch_id', $user->branch_id);
        }

        return $query;
    }

    public static function getRelations(): array
    {
        return [
            BiChecksRelationManager::class,
            PsjbsRelationManager::class,
            DocumentSubmissionsRelationManager::class,
            BankProcessesRelationManager::class,
            DeveloperPpjbsRelationManager::class,
            AkadRelationManager::class,
            BastRelationManager::class,
            CaseNotesRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListSalesCases::route('/'),
            'create' => CreateSalesCase::route('/create'),
            'view' => ViewSalesCase::route('/{record}'),
            'edit' => EditSalesCase::route('/{record}/edit'),
        ];
    }
}
