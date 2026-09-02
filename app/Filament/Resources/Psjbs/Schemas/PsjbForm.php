<?php

namespace App\Filament\Resources\Psjbs\Schemas;

use App\Models\SalesCase;
use App\Models\User;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Illuminate\Validation\Rules\Exists;

class PsjbForm
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
                Section::make('PSJB')
                    ->components([
                        DatePicker::make('psjb_date')
                            ->label('Tanggal PSJB')
                            ->default(now())
                            ->required(),
                        TextInput::make('document_number')
                            ->label('Nomor Dokumen')
                            ->maxLength(255),
                        Select::make('coordinator_id')
                            ->label('Koordinator')
                            ->options(fn (): array => User::query()->where('is_active', true)->orderBy('name')->limit(50)->pluck('name', 'id')->all())
                            ->searchable(),
                        Textarea::make('notes')
                            ->label('Catatan')
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
