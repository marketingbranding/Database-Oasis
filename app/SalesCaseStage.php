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
}
