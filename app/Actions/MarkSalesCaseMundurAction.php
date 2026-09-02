<?php

namespace App\Actions;

use App\SalesCaseStatus;

class MarkSalesCaseMundurAction extends CloseSalesCaseAction
{
    protected function status(): SalesCaseStatus
    {
        return SalesCaseStatus::Mundur;
    }
}
