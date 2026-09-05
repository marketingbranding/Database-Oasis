<?php

namespace App\Console\Commands;

use App\Models\LegacyMigrationBatch;
use App\Services\LegacyDeterministicResolutionService;
use App\Services\LegacyMigrationReadinessService;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;

#[Signature('legacy:resolve-deterministic {batch : Batch ULID}')]
#[Description('Apply approved deterministic (non-human) resolutions to a legacy migration batch')]
class ResolveLegacyDeterministic extends Command
{
    public function handle(LegacyDeterministicResolutionService $service, LegacyMigrationReadinessService $readiness): int
    {
        $batch = LegacyMigrationBatch::find($this->argument('batch'));

        if ($batch === null) {
            $this->error('Batch tidak ditemukan.');

            return self::FAILURE;
        }

        $result = $service->resolveAuthoritativeStatusCandidates($batch);
        $counts = $readiness->recalculateBatch($batch);

        $this->info('Deterministic resolutions applied: '.count($result['resolved']));
        $this->table(['Readiness', 'Count'], [
            ['AUTO', $counts['AUTO'] ?? 0],
            ['REVIEW', $counts['REVIEW'] ?? 0],
            ['BLOCKED', $counts['BLOCKED'] ?? 0],
        ]);

        return self::SUCCESS;
    }
}
