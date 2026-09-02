<?php

namespace App\Filament\Resources\SalesCases\Actions;

use App\Actions\CancelSalesCaseAction as CancelCase;
use App\Actions\MarkSalesCaseMundurAction as MarkMundur;
use App\Actions\MarkSalesCaseRejectedAction as MarkRejected;
use App\Actions\MoveSalesCaseUnitAction as MoveUnit;
use App\Models\Project;
use App\Models\SalesCase;
use App\Models\Unit;
use App\Models\User;
use App\SalesCaseStatus;
use Filament\Actions\Action;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Notifications\Notification;
use Filament\Support\Icons\Heroicon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Validation\Rules\Exists;

class CaseWorkflowActions
{
    public static function mundur(): Action
    {
        return self::closeAction('markMundur', 'Mundur', Heroicon::OutlinedArrowUturnLeft, MarkMundur::class, reasonRequired: true);
    }

    public static function reject(): Action
    {
        return self::closeAction('markRejected', 'Reject Case', Heroicon::OutlinedXCircle, MarkRejected::class, reasonRequired: true);
    }

    public static function cancel(): Action
    {
        return self::closeAction('cancelCase', 'Cancel', Heroicon::OutlinedMinusCircle, CancelCase::class, reasonRequired: false);
    }

    public static function move(): Action
    {
        return Action::make('pindahKavling')
            ->label('Pindah Kavling')
            ->icon(Heroicon::OutlinedArrowsRightLeft)
            ->color('warning')
            ->modalHeading('Pindah kavling')
            ->form([
                Select::make('new_unit_id')
                    ->label('Unit Baru')
                    ->searchable()
                    ->native(false)
                    ->options(fn (SalesCase $record): array => self::moveTargetUnitOptions($record))
                    ->getSearchResultsUsing(fn (string $search, SalesCase $record): array => self::moveTargetUnitOptions($record, $search))
                    ->getOptionLabelUsing(fn ($value): ?string => self::unitOptionLabel($value))
                    ->required()
                    ->exists(
                        'units',
                        'id',
                        modifyRuleUsing: function (Exists $rule, SalesCase $record): Exists {
                            $permittedProjectIds = Project::query()
                                ->where('branch_id', $record->branch_id)
                                ->pluck('id')
                                ->all();

                            $rule->whereIn('project_id', $permittedProjectIds);

                            return $rule;
                        },
                    ),
                Textarea::make('transfer_reason')
                    ->label('Alasan Pindah')
                    ->required()
                    ->maxLength(1000),
            ])
            ->visible(fn (SalesCase $record): bool => self::canRun($record))
            ->action(function (array $data, SalesCase $record): void {
                $user = User::current() ?? abort(403);

                app(MoveUnit::class)->handle($user, $record, $data['new_unit_id'], $data['transfer_reason']);

                Notification::make()
                    ->title('Kavling dipindah')
                    ->body('Sales case baru telah dibuat untuk unit baru.')
                    ->success()
                    ->send();
            });
    }

    private static function closeAction(string $name, string $label, Heroicon $icon, string $domainAction, bool $reasonRequired): Action
    {
        return Action::make($name)
            ->label($label)
            ->icon($icon)
            ->color('danger')
            ->requiresConfirmation()
            ->modalHeading("Tutup case: {$label}")
            ->form([
                Textarea::make('reason')
                    ->label('Alasan')
                    ->required($reasonRequired)
                    ->maxLength(1000),
            ])
            ->visible(fn (SalesCase $record): bool => self::canRun($record))
            ->action(function (array $data, SalesCase $record) use ($domainAction, $label): void {
                $user = User::current() ?? abort(403);

                app($domainAction)->handle($user, $record, $data['reason'] ?? null);

                Notification::make()
                    ->title('Sales case ditutup')
                    ->body($label)
                    ->success()
                    ->send();
            });
    }

    private static function canRun(SalesCase $record): bool
    {
        $user = User::current();

        return $record->case_status === SalesCaseStatus::Active
            && $user !== null
            && $user->can('update', $record);
    }

    /**
     * @return array<string, string>
     */
    private static function moveTargetUnitOptions(SalesCase $record, ?string $search = null): array
    {
        $units = Unit::query()
            ->with('project')
            ->whereKeyNot($record->unit_id)
            ->whereDoesntHave('activeSalesCase')
            ->whereHas('project', fn (Builder $query) => $query->where('branch_id', $record->branch_id))
            ->when(filled($search), fn (Builder $query) => $query->where('unit_code', 'like', "%{$search}%"))
            ->limit(50)
            ->get();

        return $units
            ->mapWithKeys(fn (Unit $unit): array => [$unit->id => self::unitOptionLabel($unit)])
            ->all();
    }

    private static function unitOptionLabel(mixed $value): ?string
    {
        $unit = Unit::with('project')->find($value);

        return $unit === null ? null : "{$unit->unit_code} — {$unit->project?->name}";
    }
}
