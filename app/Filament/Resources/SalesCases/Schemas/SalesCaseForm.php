<?php

namespace App\Filament\Resources\SalesCases\Schemas;

use App\FinancingType;
use App\Models\Consumer;
use App\Models\Project;
use App\Models\Unit;
use App\Models\User;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Schema;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Validation\Rules\Exists;

class SalesCaseForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Konsumen')
                    ->description('Cari berdasarkan NIK atau nama, atau buat konsumen baru.')
                    ->visible(fn (string $operation): bool => $operation === 'create')
                    ->components([
                        Select::make('consumer_id')
                            ->label('Konsumen')
                            ->searchable()
                            ->native(false)
                            ->options(fn (): array => self::consumerOptions(null))
                            ->getSearchResultsUsing(fn (string $search): array => self::consumerOptions($search))
                            ->getOptionLabelUsing(fn ($value): ?string => self::consumerLabel($value))
                            ->hidden(fn (Get $get): bool => (bool) $get('create_new_consumer')),
                        Toggle::make('create_new_consumer')
                            ->label('Konsumen baru')
                            ->live()
                            ->dehydrated(),
                        TextInput::make('new_consumer_nik')
                            ->label('NIK')
                            ->rule('digits:16')
                            ->maxLength(16)
                            ->unique(table: 'consumers', column: 'nik')
                            ->required(fn (Get $get): bool => (bool) $get('create_new_consumer'))
                            ->visible(fn (Get $get): bool => (bool) $get('create_new_consumer')),
                        TextInput::make('new_consumer_name')
                            ->label('Nama')
                            ->maxLength(255)
                            ->required(fn (Get $get): bool => (bool) $get('create_new_consumer'))
                            ->visible(fn (Get $get): bool => (bool) $get('create_new_consumer')),
                        TextInput::make('new_consumer_phone')
                            ->label('Telepon')
                            ->maxLength(255)
                            ->visible(fn (Get $get): bool => (bool) $get('create_new_consumer')),
                    ]),
                Section::make('Unit')
                    ->visible(fn (string $operation): bool => $operation === 'create')
                    ->components([
                        Select::make('unit_id')
                            ->label('Unit / Kavling')
                            ->searchable()
                            ->native(false)
                            ->options(fn (): array => self::unitOptions(null))
                            ->getSearchResultsUsing(fn (string $search): array => self::unitOptions($search))
                            ->getOptionLabelUsing(fn ($value): ?string => self::unitLabel($value))
                            ->required()
                            ->exists(
                                'units',
                                'id',
                                modifyRuleUsing: function (Exists $rule): Exists {
                                    $user = User::current();
                                    if ($user?->isBranchScoped()) {
                                        $permittedProjectIds = Project::query()
                                            ->where('branch_id', $user->branch_id)
                                            ->pluck('id')
                                            ->all();

                                        $rule->whereIn('project_id', $permittedProjectIds);
                                    }

                                    return $rule;
                                },
                            ),
                    ]),
                Section::make('Detail Case')
                    ->components([
                        Select::make('financing_type')
                            ->label('Tipe Pembiayaan')
                            ->options(FinancingType::class)
                            ->default(FinancingType::KprSubsidi)
                            ->required(),
                        DatePicker::make('booking_date')
                            ->label('Tanggal Booking'),
                        TextInput::make('source')
                            ->label('Sumber')
                            ->maxLength(255),
                        Select::make('sales_pic_id')
                            ->label('PIC Sales')
                            ->relationship('salesPic', 'name')
                            ->searchable()
                            ->preload(),
                        Select::make('coordinator_id')
                            ->label('Koordinator')
                            ->relationship('coordinator', 'name')
                            ->searchable()
                            ->preload(),
                    ]),
            ]);
    }

    /**
     * Exact 16-digit NIK searches are global so duplicate consumers are avoided
     * across branches; every other search is scoped to the user's branch.
     *
     * @return array<string, string>
     */
    private static function consumerOptions(?string $search): array
    {
        $user = User::current();
        $branchScoped = $user?->isBranchScoped() ?? false;
        $exactNik = $search !== null && preg_match('/^\d{16}$/', $search) === 1;

        $consumers = Consumer::query()
            ->when(filled($search), fn (Builder $query) => $query->where(
                fn (Builder $query) => $query
                    ->where('nik', 'like', "%{$search}%")
                    ->orWhere('name', 'like', "%{$search}%")
            ))
            ->when($branchScoped && ! $exactNik, fn (Builder $query) => $query->whereHas(
                'salesCases',
                fn (Builder $query) => $query->where('branch_id', $user->branch_id),
            ))
            ->limit(50)
            ->get();

        return $consumers
            ->mapWithKeys(fn (Consumer $consumer): array => [$consumer->id => "{$consumer->nik} — {$consumer->name}"])
            ->all();
    }

    private static function consumerLabel(mixed $value): ?string
    {
        $consumer = Consumer::find($value);

        return $consumer === null ? null : "{$consumer->nik} — {$consumer->name}";
    }

    /**
     * @return array<string, string>
     */
    private static function unitOptions(?string $search): array
    {
        $user = User::current();

        $units = Unit::query()
            ->with('project')
            ->whereDoesntHave('activeSalesCase')
            ->when(filled($search), fn (Builder $query) => $query->where(
                fn (Builder $query) => $query
                    ->where('unit_code', 'like', "%{$search}%")
                    ->orWhereHas('project', fn (Builder $query) => $query->where('name', 'like', "%{$search}%"))
            ))
            ->when($user?->isBranchScoped(), fn (Builder $query) => $query->whereHas(
                'project',
                fn (Builder $query) => $query->where('branch_id', $user->branch_id),
            ))
            ->limit(50)
            ->get();

        return $units
            ->mapWithKeys(fn (Unit $unit): array => [$unit->id => self::unitOptionLabel($unit)])
            ->all();
    }

    private static function unitLabel(mixed $value): ?string
    {
        $unit = Unit::with('project')->find($value);

        return $unit === null ? null : self::unitOptionLabel($unit);
    }

    private static function unitOptionLabel(Unit $unit): string
    {
        return "{$unit->unit_code} — {$unit->project?->name}";
    }
}
