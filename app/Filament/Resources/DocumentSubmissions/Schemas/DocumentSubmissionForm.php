<?php

namespace App\Filament\Resources\DocumentSubmissions\Schemas;

use App\Models\Bank;
use App\Models\SalesCase;
use App\Models\User;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Schema;
use Illuminate\Validation\Rules\Exists;

class DocumentSubmissionForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema->components([
            Select::make('sales_case_id')->label('Sales Case')->options(fn (): array => self::caseOptions())->searchable()->required()
                ->exists('sales_cases', 'id', modifyRuleUsing: function (Exists $rule): Exists {
                    $user = User::current();

                    return $user?->isBranchScoped() ? $rule->where('branch_id', $user->branch_id) : $rule;
                }),
            Select::make('bank_id')->label('Bank')->options(fn (): array => Bank::query()->where('is_active', true)->orderBy('name')->pluck('name', 'id')->all())->searchable()->required(),
            DatePicker::make('submission_date')->label('Tanggal Submission')->default(now())->required(),
            TextInput::make('bank_branch')->label('Cabang Bank')->maxLength(255),
            Textarea::make('notes')->label('Catatan')->maxLength(1000),
        ]);
    }

    /** @return array<string, string> */
    private static function caseOptions(): array
    {
        return SalesCase::pickableActiveCases(User::current())->with('activePsjb')->get()
            ->filter(fn (SalesCase $case): bool => $case->activePsjb !== null)
            ->mapWithKeys(fn (SalesCase $case): array => [$case->id => "{$case->consumer?->name} — {$case->unit?->unit_code}"])->all();
    }
}
