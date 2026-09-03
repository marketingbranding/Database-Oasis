<?php

namespace App\Services\Monitoring;

use Carbon\CarbonImmutable;
use Carbon\CarbonInterface;

final readonly class MonitoringPeriod
{
    public CarbonImmutable $start;

    public CarbonImmutable $end;

    public function __construct(CarbonInterface|string $month)
    {
        $this->start = CarbonImmutable::parse($month)->startOfMonth();
        $this->end = $this->start->endOfMonth();
    }

    /** @return array<string, array{0: CarbonImmutable, 1: CarbonImmutable}> */
    public function weeks(): array
    {
        return [
            'M1' => [$this->start, $this->start->setDay(7)],
            'M2' => [$this->start->setDay(8), $this->start->setDay(14)],
            'M3' => [$this->start->setDay(15), $this->start->setDay(21)],
            'M4' => [$this->start->setDay(22), $this->end],
        ];
    }

    public function bucket(CarbonInterface|string $date): string
    {
        $day = CarbonImmutable::parse($date)->day;

        return match (true) {
            $day <= 7 => 'M1',
            $day <= 14 => 'M2',
            $day <= 21 => 'M3',
            default => 'M4',
        };
    }

    public function value(): string
    {
        return $this->start->toDateString();
    }
}
