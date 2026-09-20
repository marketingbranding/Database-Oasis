<?php

namespace App\Services\Repair;

use App\Models\Branch;
use App\Models\SalesCase;
use Illuminate\Database\Eloquent\Model;

interface RepairRule
{
    public function issueCode(): string;

    public function targetType(): string;

    public function actionCode(): string;

    /** @return list<RepairIssue> */
    public function scan(Branch $branch): array;

    public function lockTarget(string $targetId): Model;

    public function detect(Model $target): RepairIssue;

    /** @return array<string, mixed> */
    public function before(Model $target): array;

    /** @return array<string, mixed> */
    public function proposed(Model $target): array;

    /** @return array<string, mixed> */
    public function fingerprintPayload(Model $target, RepairIssue $issue): array;

    public function apply(Model $target): string;

    /** @param array<string, mixed> $before */
    public function verify(Model $target, SalesCase $case, array $before): void;
}
