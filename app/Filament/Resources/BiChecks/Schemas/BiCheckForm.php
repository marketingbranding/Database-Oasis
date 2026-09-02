<?php

namespace App\Filament\Resources\BiChecks\Schemas;

use App\BiCheckResult;
use App\Models\SalesCase;
use App\Models\User;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Illuminate\Validation\Rules\Exists;

class BiCheckForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Sales Case')
                    ->components([
                        Select::make('sales_case_id')
                            ->label('Sales Case')
                            ->searchable()
                            ->native(false)
                            ->options(fn (): array => self::caseOptions(null))
                            ->getSearchResultsUsing(fn (string $search): array => self::caseOptions($search))
                            ->getOptionLabelUsing(fn ($value): ?string => self::caseLabel($value))
                            ->required()
                            ->exists(
                                'sales_cases',
                                'id',
                                modifyRuleUsing: function (Exists $rule): Exists {
                                    $user = User::current();
                                    if ($user?->isBranchScoped()) {
                                        $rule->where('branch_id', $user->branch_id);
                                    }

                                    return $rule;
                                },
                            ),
                    ]),
                Section::make('BI Check')
                    ->components([
                        DatePicker::make('check_date')
                            ->label('Tanggal Cek')
                            ->default(now())
                            ->required(),
                        Select::make('result')
                            ->label('Hasil')
                            ->options(BiCheckResult::class)
                            ->required(),
                        Textarea::make('description')
                            ->label('Keterangan')
                            ->maxLength(1000),
                    ]),
            ]);
    }

    /**
     * @return array<string, string>
     */
    private static function caseOptions(?string $search): array
    {
        $cases = SalesCase::pickableActiveCases(User::current(), $search)
            ->limit(50)
            ->get();

        return $cases
            ->mapWithKeys(fn (SalesCase $case): array => [$case->id => self::caseOptionLabel($case)])
            ->all();
    }

    private static function caseLabel(mixed $value): ?string
    {
        $case = SalesCase::with(['consumer', 'unit'])->find($value);

        return $case === null ? null : self::caseOptionLabel($case);
    }

    private static function caseOptionLabel(SalesCase $case): string
    {
        return "{$case->consumer?->name} — {$case->unit?->unit_code}";
    }
}
