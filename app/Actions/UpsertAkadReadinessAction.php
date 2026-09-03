<?php

namespace App\Actions;

use App\DpStatus;
use App\Models\AkadReadiness;
use App\Models\SalesCase;
use App\Models\User;
use App\ReadinessIssueStatus;
use App\ReadinessUtilityStatus;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class UpsertAkadReadinessAction
{
    /** @param array<string, mixed> $data */
    public function handle(User $actor, SalesCase $case, array $data): AkadReadiness
    {
        return DB::transaction(function () use ($actor, $case, $data): AkadReadiness {
            $case = SalesCase::query()->lockForUpdate()->findOrFail($case->id);
            Gate::forUser($actor)->authorize('update', $case);

            if ($case->akad()->exists()) {
                throw ValidationException::withMessages(['sales_case_id' => 'Readiness tidak dapat diubah setelah Akad.']);
            }

            $validated = validator($data, [
                'building_progress' => ['nullable', 'integer', 'between:0,100'],
                'building_status' => ['required', Rule::enum(ReadinessIssueStatus::class)],
                'dp_status' => ['required', Rule::enum(DpStatus::class)],
                'electricity_status' => ['required', Rule::enum(ReadinessUtilityStatus::class)],
                'water_status' => ['required', Rule::enum(ReadinessUtilityStatus::class)],
                'consumer_status' => ['required', Rule::enum(ReadinessIssueStatus::class)],
                'consumer_note' => ['nullable', 'string', 'max:2000'],
                'notes' => ['nullable', 'string', 'max:5000'],
            ])->validate();

            return $case->akadReadiness()->updateOrCreate(
                ['sales_case_id' => $case->id],
                [...$validated, 'updated_by' => $actor->id],
            );
        });
    }
}
