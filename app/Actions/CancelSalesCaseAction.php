<?php

namespace App\Actions;

use App\SalesCaseStatus;

class CancelSalesCaseAction extends CloseSalesCaseAction
{
    protected function status(): SalesCaseStatus
    {
        return SalesCaseStatus::Cancelled;
    }
}
