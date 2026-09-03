<?php

namespace App\Filament\Pages;

use App\Models\AkadRecord;
use App\Models\User;
use App\Services\Monitoring\MonitoringPeriod;
use App\Services\Monitoring\MonitoringScope;
use App\Services\Monitoring\MonitoringService;
use App\UserRole;
use BackedEnum;
use Filament\Pages\Page;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Tables\Contracts\HasTable;
use Filament\Tables\Table;
use Livewire\Attributes\Url;
use UnitEnum;

class AkadMonitoring extends Page implements HasTable
{
    use InteractsWithTable;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedScale;

    protected static string|UnitEnum|null $navigationGroup = 'Monitoring';

    protected static ?string $navigationLabel = 'Realisasi Akad';

    protected static ?int $navigationSort = 3;

    protected string $view = 'filament.pages.akad-monitoring';

    #[Url]
    public string $month = '';

    #[Url(as: 'branch')]
    public ?string $branchId = null;

    #[Url(as: 'project')]
    public ?string $projectId = null;

    public function mount(): void
    {
        $this->month = $this->month !== '' ? $this->month : now()->format('Y-m');
    }

    public static function canAccess(): bool
    {
        return User::current()?->hasAnyRole([UserRole::SuperAdmin, UserRole::HqAdmin, UserRole::BranchAdmin, UserRole::BranchManager, UserRole::Management, UserRole::Auditor]) ?? false;
    }

    public function table(Table $table): Table
    {
        $period = new MonitoringPeriod($this->month);
        $query = app(MonitoringService::class)->akadQuery($period, new MonitoringScope(User::current() ?? abort(403), $this->branchId, $this->projectId, strict: false))
            ->with(['salesCase.consumer', 'salesCase.branch', 'salesCase.project', 'salesCase.unit', 'bast']);

        return $table
            ->query($query)
            ->columns([
                TextColumn::make('salesCase.consumer.name')->label('Konsumen')->searchable()->sortable(),
                TextColumn::make('salesCase.branch.name')->label('Cabang')->sortable(),
                TextColumn::make('salesCase.project.name')->label('Proyek')->sortable(),
                TextColumn::make('salesCase.unit.unit_code')->label('Unit')->searchable(),
                TextColumn::make('salesCase.financing_type')->label('Pembiayaan')->badge(),
                TextColumn::make('akad_date')->label('Tanggal Akad')->date()->sortable(),
                TextColumn::make('week')->label('Minggu')->state(fn (AkadRecord $record): string => $period->bucket($record->akad_date))->badge(),
                TextColumn::make('bast.status')->label('BAST')->badge()->placeholder('Belum BAST'),
            ])
            ->defaultSort('akad_date', 'desc');
    }
}
