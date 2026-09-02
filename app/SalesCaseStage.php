<?php

namespace App;

use Filament\Support\Contracts\HasLabel;

enum SalesCaseStage: string implements HasLabel
{
    case DataKonsumen = 'DATA_KONSUMEN';
    case BiChecking = 'BI_CHECKING';
    case Psjb = 'PSJB';
    case Pemberkasan = 'PEMBERKASAN';
    case ProsesBank = 'PROSES_BANK';
    case PpjbDev = 'PPJB_DEV';
    case Akad = 'AKAD';
    case Bast = 'BAST';
    case Completed = 'COMPLETED';

    public function getLabel(): string
    {
        return match ($this) {
            self::DataKonsumen => 'Data Konsumen',
            self::BiChecking => 'BI Checking',
            self::Psjb => 'PSJB',
            self::Pemberkasan => 'Pemberkasan',
            self::ProsesBank => 'Proses Bank',
            self::PpjbDev => 'PPJB Developer',
            self::Akad => 'Akad',
            self::Bast => 'BAST',
            self::Completed => 'Completed',
        };
    }

    public function order(): int
    {
        return match ($this) {
            self::DataKonsumen => 1,
            self::BiChecking => 2,
            self::Psjb => 3,
            self::Pemberkasan => 4,
            self::ProsesBank => 5,
            self::PpjbDev => 6,
            self::Akad => 7,
            self::Bast => 8,
            self::Completed => 9,
        };
    }

    public function isBeyond(self $other): bool
    {
        return $this->order() > $other->order();
    }
}
