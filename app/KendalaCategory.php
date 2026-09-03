<?php

namespace App;

use Filament\Support\Contracts\HasLabel;

enum KendalaCategory: string implements HasLabel
{
    case Bangunan = 'BANGUNAN';
    case DpKonsumen = 'DP_KONSUMEN';
    case Utilitas = 'UTILITAS';
    case Konsumen = 'KONSUMEN';

    public function getLabel(): string
    {
        return match ($this) {
            self::Bangunan => 'Bangunan',
            self::DpKonsumen => 'DP Konsumen',
            self::Utilitas => 'Utilitas',
            self::Konsumen => 'Konsumen',
        };
    }
}
