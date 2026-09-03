<?php

namespace App\Filament\Pages;

use App\KendalaCategory;
use App\Models\Branch;
use App\Models\Project;
use App\Models\User;
use App\Services\Monitoring\MonitoringPeriod;
use App\Services\Monitoring\MonitoringScope;
use App\Services\Monitoring\MonitoringService;
use App\Sp3kAgingBucket;
use App\UserRole;
use BackedEnum;
use Filament\Pages\Page;
use Filament\Support\Icons\Heroicon;
use Illuminate\Database\Eloquent\Builder;
use UnitEnum;

class Monitoring extends Page
{
    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedChartBarSquare;

    protected static string|UnitEnum|null $navigationGroup = 'Monitoring';

    protected static ?string $navigationLabel = 'Monitoring';

    protected static ?int $navigationSort = 1;

    protected string $view = 'filament.pages.monitoring';

    public string $month = '';

    public ?string $branchId = null;

    public ?string $projectId = null;

    public function mount(): void
    {
        $this->month = $this->month !== '' ? $this->month : now()->startOfMonth()->format('Y-m');
        $user = User::current();
        $this->branchId = $user?->isBranchScoped() ? $user->branch_id : null;
    }

    public static function canAccess(): bool
    {
        return User::current()?->hasAnyRole([
            UserRole::SuperAdmin,
            UserRole::HqAdmin,
            UserRole::BranchAdmin,
            UserRole::BranchManager,
            UserRole::Management,
            UserRole::Auditor,
        ]) ?? false;
    }

    public function updatedBranchId(): void
    {
        $this->projectId = null;
    }

    /** @return array<string, mixed> */
    protected function getViewData(): array
    {
        $user = User::current() ?? abort(403);
        $scope = new MonitoringScope($user, $this->branchId, $this->projectId, strict: false);
        $period = new MonitoringPeriod($this->month);
        $service = app(MonitoringService::class);

        return [
            'branches' => $this->branchOptions($user),
            'projects' => $this->projectOptions($scope),
            'metrics' => $service->overview($period, $scope),
            'period' => $period,
            'issueLabels' => collect(KendalaCategory::cases())->mapWithKeys(fn (KendalaCategory $category): array => [$category->value => $category->getLabel()])->all(),
            'agingLabels' => collect(Sp3kAgingBucket::cases())->mapWithKeys(fn (Sp3kAgingBucket $bucket): array => [$bucket->value => $bucket->getLabel()])->all(),
        ];
    }

    /** @return array<string, string> */
    private function branchOptions(User $user): array
    {
        return Branch::query()
            ->when($user->isBranchScoped(), fn (Builder $query) => $query->whereKey($user->branch_id))
            ->orderBy('name')
            ->pluck('name', 'id')
            ->all();
    }

    /** @return array<string, string> */
    private function projectOptions(MonitoringScope $scope): array
    {
        return Project::query()
            ->when($scope->branchId() !== null, fn (Builder $query) => $query->where('branch_id', $scope->branchId()))
            ->orderBy('name')
            ->pluck('name', 'id')
            ->all();
    }
}
