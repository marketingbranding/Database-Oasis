<?php

namespace App\Actions;

use App\Models\CaseNote;
use App\Models\SalesCase;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\ValidationException;

class CreateCaseNoteAction
{
    /** @param array<string, mixed> $data */
    public function handle(User $user, array $data): CaseNote
    {
        Gate::forUser($user)->authorize('create', CaseNote::class);

        return DB::transaction(function () use ($user, $data): CaseNote {
            /** @var SalesCase $case */
            $case = SalesCase::whereKey($data['sales_case_id'] ?? null)->lockForUpdate()->firstOrFail();

            if ($user->isBranchScoped() && ! $user->belongsToBranch($case->branch_id)) {
                throw ValidationException::withMessages(['sales_case_id' => 'Sales case berada di luar cabang Anda.']);
            }

            if (blank($data['note'] ?? null)) {
                throw ValidationException::withMessages(['note' => 'Catatan wajib diisi.']);
            }

            /** @var CaseNote $note */
            $note = CaseNote::create([
                'sales_case_id' => $case->id,
                'note' => $data['note'],
                'created_by' => $user->id,
            ]);

            return $note;
        });
    }
}
