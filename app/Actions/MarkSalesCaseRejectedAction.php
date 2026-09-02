<?php

namespace App\Actions;

use App\SalesCaseStatus;

class MarkSalesCaseRejectedAction extends CloseSalesCaseAction
{
    protected function status(): SalesCaseStatus
    {
        return SalesCaseStatus::Reject;
    }
}
