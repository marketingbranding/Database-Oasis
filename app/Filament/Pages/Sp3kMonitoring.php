<?php

namespace App\Filament\Pages;

use App\KendalaCategory;
use App\Models\Branch;
use App\Models\Project;
use App\Models\SalesCase;
use App\Models\User;
use App\Services\Monitoring\MonitoringScope;
use App\Services\Monitoring\MonitoringService;
use App\Sp3kAgingBucket;
use App\UserRole;
use BackedEnum;
use Carbon\Carbon;
use Filament\Pages\Page;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Tables\Contracts\HasTable;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Livewire\Attributes\Url;
use UnitEnum;

class Sp3kMonitoring extends Page implements HasTable
{
    use InteractsWithTable;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedBuildingLibrary;

    protected static string|UnitEnum|null $navigationGroup = 'Monitoring';

    protected static ?string $navigationLabel = 'SP3K & Kendala';

    protected static ?int $navigationSort = 2;

    protected string $view = 'filament.pages.sp3k-monitoring';

    #[Url(as: 'branch')]
    public ?string $branchId = null;

    #[Url(as: 'project')]
    public ?string $projectId = null;

    #[Url(as: 'aging')]
    public ?string $aging = null;

    #[Url(as: 'issue')]
    public ?string $issue = null;

    public static function canAccess(): bool
    {
        return User::current()?->hasAnyRole([UserRole::SuperAdmin, UserRole::HqAdmin, UserRole::BranchAdmin, UserRole::BranchManager, UserRole::Management, UserRole::Auditor]) ?? false;
    }

    public function table(Table $table): Table
    {
        $service = app(MonitoringService::class);
        $query = $service->sp3kStockQuery($this->scope())
            ->with(['consumer', 'branch', 'project', 'unit', 'currentApprovedBankProcess.bank', 'akadReadiness']);

        if (($bucket = Sp3kAgingBucket::tryFrom((string) $this->aging)) !== null) {
            $service->applyAgingBucket($query, $bucket);
        }
        if (($category = KendalaCategory::tryFrom((string) $this->issue)) !== null) {
            $service->applyIssueCategory($query, $category);
        }

        return $table
            ->query($query)
            ->columns([
                TextColumn::make('consumer.name')->label('Konsumen')->searchable()->sortable(),
                TextColumn::make('branch.name')->label('Cabang')->sortable(),
                TextColumn::make('project.name')->label('Proyek')->sortable(),
                TextColumn::make('unit.unit_code')->label('Unit')->searchable(),
                TextColumn::make('currentApprovedBankProcess.bank.name')->label('Bank'),
                TextColumn::make('currentApprovedBankProcess.sp3k_number')->label('SP3K')->searchable(),
                TextColumn::make('currentApprovedBankProcess.sp3k_date')->label('Tanggal SP3K')->date()->sortable(),
                TextColumn::make('sp3k_aging')->label('Aging')->state(fn (SalesCase $record): string => $record->currentApprovedBankProcess->sp3k_date->startOfDay()->diffInDays(Carbon::today()).' hari'),
                TextColumn::make('akadReadiness.building_progress')->label('Bangunan')->suffix('%')->placeholder('-'),
                TextColumn::make('akadReadiness.building_status')->label('Status Bangunan')->badge()->placeholder('UNKNOWN'),
                TextColumn::make('akadReadiness.dp_status')->label('DP')->badge()->placeholder('UNKNOWN'),
                TextColumn::make('akadReadiness.electricity_status')->label('Listrik')->badge()->placeholder('UNKNOWN'),
                TextColumn::make('akadReadiness.water_status')->label('Air')->badge()->placeholder('UNKNOWN'),
                TextColumn::make('akadReadiness.consumer_status')->label('Konsumen')->badge()->placeholder('UNKNOWN'),
                TextColumn::make('issue_count')->label('Jumlah Kendala')->state(fn (SalesCase $record): int => $record->akadReadiness?->issueCount() ?? 0),
            ])
            ->filters([
                SelectFilter::make('branch')->options(fn (): array => Branch::query()
                    ->when($this->scope()->branchId() !== null, fn (Builder $query) => $query->whereKey($this->scope()->branchId()))
                    ->orderBy('name')->pluck('name', 'id')->all())
                    ->query(fn (Builder $query, array $data): Builder => filled($data['value'] ?? null) ? $query->where('branch_id', $data['value']) : $query),
                SelectFilter::make('project')->options(fn (): array => Project::query()
                    ->when($this->scope()->branchId() !== null, fn (Builder $query) => $query->where('branch_id', $this->scope()->branchId()))
                    ->orderBy('name')->pluck('name', 'id')->all())
                    ->query(fn (Builder $query, array $data): Builder => filled($data['value'] ?? null) ? $query->where('project_id', $data['value']) : $query),
                SelectFilter::make('bank')->relationship('currentApprovedBankProcess.bank', 'name')->searchable()->preload(),
                SelectFilter::make('aging')->options(Sp3kAgingBucket::class)->query(fn (Builder $query, array $data): Builder => ($bucket = Sp3kAgingBucket::tryFrom((string) ($data['value'] ?? ''))) === null ? $query : $service->applyAgingBucket($query, $bucket)),
                SelectFilter::make('issue')->options(KendalaCategory::class)->query(fn (Builder $query, array $data): Builder => ($category = KendalaCategory::tryFrom((string) ($data['value'] ?? ''))) === null ? $query : $service->applyIssueCategory($query, $category)),
            ])
            ->defaultSort('created_at', 'desc');
    }

    private function scope(): MonitoringScope
    {
        return new MonitoringScope(User::current() ?? abort(403), $this->branchId, $this->projectId, strict: false);
    }
}
