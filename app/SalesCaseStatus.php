<?php

namespace App;

use Filament\Support\Contracts\HasLabel;

enum SalesCaseStatus: string implements HasLabel
{
    case Active = 'ACTIVE';
    case Completed = 'COMPLETED';
    case Mundur = 'MUNDUR';
    case Reject = 'REJECT';

    /**
     * Legacy: a unit transfer is an event on the same ACTIVE sales case, not a
     * terminal status. Retained so historical rows still cast correctly.
     */
    case PindahKavling = 'PINDAH_KAVLING';

    /**
     * Legacy: superseded by Mundur/Reject. Retained so historical rows still
     * cast correctly.
     */
    case Cancelled = 'CANCELLED';

    public function getLabel(): string
    {
        return match ($this) {
            self::Active => 'Aktif',
            self::Completed => 'Selesai',
            self::Mundur => 'Mundur',
            self::Reject => 'Ditolak',
            self::PindahKavling => 'Pindah Kavling',
            self::Cancelled => 'Dibatalkan',
        };
    }

    /**
     * Business-visible current statuses. PINDAH_KAVLING and CANCELLED are
     * historical only and must not be offered as new case statuses.
     *
     * @return list<self>
     */
    public static function current(): array
    {
        return [self::Active, self::Completed, self::Mundur, self::Reject];
    }

    /**
     * @return list<string>
     */
    public static function currentValues(): array
    {
        return array_map(fn (self $status): string => $status->value, self::current());
    }

    public function isCurrent(): bool
    {
        return in_array($this, self::current(), true);
    }

    public function isLegacy(): bool
    {
        return ! $this->isCurrent();
    }
}
