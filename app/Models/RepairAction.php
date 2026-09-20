<?php

namespace App\Models;

use App\Repairability;
use Illuminate\Database\Eloquent\Attributes\Fillable;

#[Fillable(['branch_id', 'sales_case_id', 'issue_code', 'target_type', 'target_id', 'action_code', 'repairability', 'plan_fingerprint', 'before_payload', 'after_payload', 'evidence_payload', 'performed_by'])]
class RepairAction extends Model
{
    protected function casts(): array
    {
        return [
            'repairability' => Repairability::class,
            'before_payload' => 'array',
            'after_payload' => 'array',
            'evidence_payload' => 'array',
        ];
    }
}
