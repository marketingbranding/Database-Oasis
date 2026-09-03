<?php

namespace App\Services\Monitoring;

use App\BankResponseType;
use App\BastStatus;
use App\DpStatus;
use App\FinancingType;
use App\KendalaCategory;
use App\Models\AkadRecord;
use App\Models\AkadTarget;
use App\Models\BastRecord;
use App\Models\SalesCase;
use App\ReadinessIssueStatus;
use App\ReadinessUtilityStatus;
use App\SalesCaseStatus;
use App\Sp3kAgingBucket;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Builder;

class MonitoringService
{
    /** @return array{target: ?int, akad: int, achievement: ?float, weekly: array<string, int>, sp3k_stock: int, sp3k_with_issues: int, open_issues: int, issue_breakdown: array<string, int>, sp3k_aging: array<string, int>, readiness_incomplete: int, bast: int} */
    public function overview(MonitoringPeriod $period, MonitoringScope $scope): array
    {
        $target = $this->akadTarget($period, $scope);
        $weekly = $this->akadWeeklyBreakdown($period, $scope);
        $akad = array_sum($weekly);
        $issues = $this->issueBreakdown($scope);

        return [
            'target' => $target,
            'akad' => $akad,
            'achievement' => $target !== null && $target > 0 ? round(($akad / $target) * 100, 1) : null,
            'weekly' => $weekly,
            'sp3k_stock' => $this->sp3kStockQuery($scope)->count(),
            'sp3k_with_issues' => $this->sp3kWithIssuesQuery($scope)->count(),
            'open_issues' => array_sum($issues),
            'issue_breakdown' => $issues,
            'sp3k_aging' => $this->sp3kAging($scope),
            'readiness_incomplete' => $this->readinessIncompleteQuery($scope)->count(),
            'bast' => $this->bastRealization($period, $scope),
        ];
    }

    public function akadTarget(MonitoringPeriod $period, MonitoringScope $scope): ?int
    {
        $query = AkadTarget::query()->whereDate('period_month', $period->value());

        if ($scope->projectId !== null) {
            return $query->where('project_id', $scope->projectId)->value('target');
        }

        $query->whereNull('project_id');
        if ($scope->branchId() !== null) {
            return $query->where('branch_id', $scope->branchId())->value('target');
        }

        $count = (clone $query)->count();

        return $count === 0 ? null : (int) $query->sum('target');
    }

    public function akadRealization(MonitoringPeriod $period, MonitoringScope $scope): int
    {
        return $this->akadQuery($period, $scope)->count();
    }

    /** @return array{M1: int, M2: int, M3: int, M4: int} */
    public function akadWeeklyBreakdown(MonitoringPeriod $period, MonitoringScope $scope): array
    {
        $weeks = $period->weeks();
        $result = $this->akadQuery($period, $scope)
            ->toBase()
            ->selectRaw('count(case when akad_date >= ? and akad_date < ? then 1 end) as m1', [$weeks['M1'][0]->toDateString(), $weeks['M1'][1]->addDay()->toDateString()])
            ->selectRaw('count(case when akad_date >= ? and akad_date < ? then 1 end) as m2', [$weeks['M2'][0]->toDateString(), $weeks['M2'][1]->addDay()->toDateString()])
            ->selectRaw('count(case when akad_date >= ? and akad_date < ? then 1 end) as m3', [$weeks['M3'][0]->toDateString(), $weeks['M3'][1]->addDay()->toDateString()])
            ->selectRaw('count(case when akad_date >= ? and akad_date < ? then 1 end) as m4', [$weeks['M4'][0]->toDateString(), $weeks['M4'][1]->addDay()->toDateString()])
            ->first();

        return [
            'M1' => (int) ($result->m1 ?? 0),
            'M2' => (int) ($result->m2 ?? 0),
            'M3' => (int) ($result->m3 ?? 0),
            'M4' => (int) ($result->m4 ?? 0),
        ];
    }

    /** @return Builder<SalesCase> */
    public function sp3kStockQuery(MonitoringScope $scope): Builder
    {
        return $this->scopeSalesCases(SalesCase::query(), $scope)
            ->where('financing_type', FinancingType::KprSubsidi->value)
            ->where('case_status', SalesCaseStatus::Active->value)
            ->whereDoesntHave('akad')
            ->whereHas('currentApprovedBankProcess', fn (Builder $query) => $query
                ->where('response_type', BankResponseType::Approved->value)
                ->whereNotNull('sp3k_number')
                ->whereNotNull('sp3k_date'));
    }

    /** @return Builder<SalesCase> */
    public function sp3kWithIssuesQuery(MonitoringScope $scope): Builder
    {
        return $this->sp3kStockQuery($scope)->whereHas('akadReadiness', fn (Builder $query) => $query->where(function (Builder $query): void {
            $query->where('building_status', ReadinessIssueStatus::Issue->value)
                ->orWhere('dp_status', DpStatus::Incomplete->value)
                ->orWhere('electricity_status', ReadinessUtilityStatus::NotInstalled->value)
                ->orWhere('water_status', ReadinessUtilityStatus::NotInstalled->value)
                ->orWhere('consumer_status', ReadinessIssueStatus::Issue->value);
        }));
    }

    /** @param Builder<SalesCase> $query
     * @return Builder<SalesCase>
     */
    public function applyAgingBucket(Builder $query, Sp3kAgingBucket $bucket, ?CarbonImmutable $today = null): Builder
    {
        $today ??= CarbonImmutable::today();

        return match ($bucket) {
            Sp3kAgingBucket::ZeroToSeven => $this->filterSp3kBetween($query, $today->subDays(7), $today),
            Sp3kAgingBucket::EightToFourteen => $this->filterSp3kBetween($query, $today->subDays(14), $today->subDays(8)),
            Sp3kAgingBucket::FifteenToThirty => $this->filterSp3kBetween($query, $today->subDays(30), $today->subDays(15)),
            Sp3kAgingBucket::OverThirty => $query->whereHas('currentApprovedBankProcess', fn (Builder $query) => $query->whereDate('sp3k_date', '<', $today->subDays(30))),
        };
    }

    /** @param Builder<SalesCase> $query
     * @return Builder<SalesCase>
     */
    public function applyIssueCategory(Builder $query, KendalaCategory $category): Builder
    {
        return match ($category) {
            KendalaCategory::Bangunan => $query->whereHas('akadReadiness', fn (Builder $query) => $query->where('building_status', ReadinessIssueStatus::Issue->value)),
            KendalaCategory::DpKonsumen => $query->whereHas('akadReadiness', fn (Builder $query) => $query->where('dp_status', DpStatus::Incomplete->value)),
            KendalaCategory::Utilitas => $query->whereHas('akadReadiness', fn (Builder $query) => $query->where(fn (Builder $query) => $query
                ->where('electricity_status', ReadinessUtilityStatus::NotInstalled->value)
                ->orWhere('water_status', ReadinessUtilityStatus::NotInstalled->value))),
            KendalaCategory::Konsumen => $query->whereHas('akadReadiness', fn (Builder $query) => $query->where('consumer_status', ReadinessIssueStatus::Issue->value)),
        };
    }

    /** @return Builder<SalesCase> */
    public function readinessIncompleteQuery(MonitoringScope $scope): Builder
    {
        return $this->sp3kStockQuery($scope)->where(function (Builder $query): void {
            $query->whereDoesntHave('akadReadiness')
                ->orWhereHas('akadReadiness', fn (Builder $query) => $query->where(function (Builder $query): void {
                    $query->where('building_status', ReadinessIssueStatus::Unknown->value)
                        ->orWhere('dp_status', DpStatus::Unknown->value)
                        ->orWhere('electricity_status', ReadinessUtilityStatus::Unknown->value)
                        ->orWhere('water_status', ReadinessUtilityStatus::Unknown->value)
                        ->orWhere('consumer_status', ReadinessIssueStatus::Unknown->value);
                }));
        });
    }

    /** @return array<string, int> */
    public function issueBreakdown(MonitoringScope $scope): array
    {
        $base = $this->sp3kStockQuery($scope);

        return [
            KendalaCategory::Bangunan->value => (clone $base)->whereHas('akadReadiness', fn (Builder $query) => $query->where('building_status', ReadinessIssueStatus::Issue->value))->count(),
            KendalaCategory::DpKonsumen->value => (clone $base)->whereHas('akadReadiness', fn (Builder $query) => $query->where('dp_status', DpStatus::Incomplete->value))->count(),
            KendalaCategory::Utilitas->value => (clone $base)->whereHas('akadReadiness', fn (Builder $query) => $query->where(fn (Builder $query) => $query
                ->where('electricity_status', ReadinessUtilityStatus::NotInstalled->value)
                ->orWhere('water_status', ReadinessUtilityStatus::NotInstalled->value)))->count(),
            KendalaCategory::Konsumen->value => (clone $base)->whereHas('akadReadiness', fn (Builder $query) => $query->where('consumer_status', ReadinessIssueStatus::Issue->value))->count(),
        ];
    }

    /** @return array<string, int> */
    public function sp3kAging(MonitoringScope $scope, ?CarbonImmutable $today = null): array
    {
        $today ??= CarbonImmutable::today();
        $base = $this->sp3kStockQuery($scope);

        return [
            Sp3kAgingBucket::ZeroToSeven->value => $this->countSp3kBetween(clone $base, $today->subDays(7), $today),
            Sp3kAgingBucket::EightToFourteen->value => $this->countSp3kBetween(clone $base, $today->subDays(14), $today->subDays(8)),
            Sp3kAgingBucket::FifteenToThirty->value => $this->countSp3kBetween(clone $base, $today->subDays(30), $today->subDays(15)),
            Sp3kAgingBucket::OverThirty->value => (clone $base)->whereHas('currentApprovedBankProcess', fn (Builder $query) => $query->whereDate('sp3k_date', '<', $today->subDays(30)))->count(),
        ];
    }

    public function bastRealization(MonitoringPeriod $period, MonitoringScope $scope): int
    {
        return BastRecord::query()
            ->where('status', BastStatus::Completed->value)
            ->where('bast_date', '>=', $period->start->toDateString())
            ->where('bast_date', '<', $period->end->addDay()->toDateString())
            ->whereHas('salesCase', fn (Builder $query) => $query
                ->when($scope->branchId() !== null, fn (Builder $query) => $query->where('branch_id', $scope->branchId()))
                ->when($scope->projectId !== null, fn (Builder $query) => $query->where('project_id', $scope->projectId)))
            ->count();
    }

    /** @return Builder<AkadRecord> */
    public function akadQuery(MonitoringPeriod $period, MonitoringScope $scope): Builder
    {
        return AkadRecord::query()
            ->where('akad_date', '>=', $period->start->toDateString())
            ->where('akad_date', '<', $period->end->addDay()->toDateString())
            ->whereHas('salesCase', fn (Builder $query) => $query
                ->when($scope->branchId() !== null, fn (Builder $query) => $query->where('branch_id', $scope->branchId()))
                ->when($scope->projectId !== null, fn (Builder $query) => $query->where('project_id', $scope->projectId)));
    }

    /** @param Builder<SalesCase> $query
     * @return Builder<SalesCase>
     */
    private function scopeSalesCases(Builder $query, MonitoringScope $scope): Builder
    {
        return $query
            ->when($scope->branchId() !== null, fn (Builder $query) => $query->where('branch_id', $scope->branchId()))
            ->when($scope->projectId !== null, fn (Builder $query) => $query->where('project_id', $scope->projectId));
    }

    /** @param Builder<SalesCase> $query */
    private function countSp3kBetween(Builder $query, CarbonImmutable $start, CarbonImmutable $end): int
    {
        return $this->filterSp3kBetween($query, $start, $end)->count();
    }

    /** @param Builder<SalesCase> $query
     * @return Builder<SalesCase>
     */
    private function filterSp3kBetween(Builder $query, CarbonImmutable $start, CarbonImmutable $end): Builder
    {
        return $query->whereHas('currentApprovedBankProcess', fn (Builder $query) => $query
            ->where('sp3k_date', '>=', $start->toDateString())
            ->where('sp3k_date', '<', $end->addDay()->toDateString()));
    }
}
