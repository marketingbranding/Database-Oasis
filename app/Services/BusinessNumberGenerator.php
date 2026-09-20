<?php

namespace App\Services;

use App\Enums\BusinessNumberType;
use App\Models\BankProcess;
use App\Models\Branch;
use App\Models\BusinessNumberSequence;
use App\Models\DeveloperPpjb;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

final class BusinessNumberGenerator
{
    public function ensureSp3kCode(BankProcess $process): string
    {
        if ($process->sp3k_code !== null) {
            return $process->sp3k_code;
        }
        if (! $process->is_authoritative || $process->sp3k_date === null) {
            throw ValidationException::withMessages(['sp3k_code' => 'SP3K canonical code requires authoritative approval and SP3K date.']);
        }

        return DB::transaction(function () use ($process): string {
            $process->refresh();
            if ($process->sp3k_code !== null) {
                return $process->sp3k_code;
            }
            $branch = $process->salesCase->branch;
            $code = $this->next(BusinessNumberType::Sp3k, $branch, (int) $process->sp3k_date->format('Y'));
            $process->forceFill(['sp3k_code' => $code])->save();

            return $code;
        });
    }

    public function ensurePpjbCode(DeveloperPpjb $ppjb): string
    {
        if ($ppjb->ppjb_code !== null) {
            return $ppjb->ppjb_code;
        }

        return DB::transaction(function () use ($ppjb): string {
            $ppjb->refresh();
            if ($ppjb->ppjb_code !== null) {
                return $ppjb->ppjb_code;
            }
            $branch = $ppjb->salesCase->branch;
            $code = $this->next(BusinessNumberType::DeveloperPpjb, $branch, (int) $ppjb->document_date->format('Y'));
            $ppjb->forceFill(['ppjb_code' => $code])->save();

            return $code;
        });
    }

    public function next(BusinessNumberType $type, Branch $branch, int $year): string
    {
        return DB::transaction(function () use ($type, $branch, $year): string {
            BusinessNumberSequence::query()->firstOrCreate(['type' => $type->value, 'branch_id' => $branch->id, 'year' => $year]);
            $sequence = BusinessNumberSequence::query()->where(['type' => $type->value, 'branch_id' => $branch->id, 'year' => $year])->lockForUpdate()->firstOrFail();
            $number = $sequence->last_number + 1;
            $sequence->update(['last_number' => $number]);

            return sprintf('%s-%s-%d-%06d', $type->value, strtoupper($branch->code), $year, $number);
        });
    }
}
