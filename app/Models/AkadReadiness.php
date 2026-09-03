<?php

namespace App\Models;

use App\DpStatus;
use App\KendalaCategory;
use App\ReadinessIssueStatus;
use App\ReadinessUtilityStatus;
use Database\Factories\AkadReadinessFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['sales_case_id', 'building_progress', 'building_status', 'dp_status', 'electricity_status', 'water_status', 'consumer_status', 'consumer_note', 'notes', 'updated_by'])]
class AkadReadiness extends Model
{
    /** @use HasFactory<AkadReadinessFactory> */
    use HasFactory;

    protected $table = 'akad_readiness';

    protected function casts(): array
    {
        return [
            'building_progress' => 'integer',
            'building_status' => ReadinessIssueStatus::class,
            'dp_status' => DpStatus::class,
            'electricity_status' => ReadinessUtilityStatus::class,
            'water_status' => ReadinessUtilityStatus::class,
            'consumer_status' => ReadinessIssueStatus::class,
        ];
    }

    /** @return BelongsTo<SalesCase, $this> */
    public function salesCase(): BelongsTo
    {
        return $this->belongsTo(SalesCase::class);
    }

    /** @return BelongsTo<User, $this> */
    public function updatedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

    /** @return array<int, KendalaCategory> */
    public function issueCategories(): array
    {
        return array_values(array_filter([
            $this->building_status === ReadinessIssueStatus::Issue ? KendalaCategory::Bangunan : null,
            $this->dp_status === DpStatus::Incomplete ? KendalaCategory::DpKonsumen : null,
            $this->electricity_status === ReadinessUtilityStatus::NotInstalled || $this->water_status === ReadinessUtilityStatus::NotInstalled ? KendalaCategory::Utilitas : null,
            $this->consumer_status === ReadinessIssueStatus::Issue ? KendalaCategory::Konsumen : null,
        ]));
    }

    public function issueCount(): int
    {
        return count($this->issueCategories());
    }

    public function isComplete(): bool
    {
        return $this->building_status !== ReadinessIssueStatus::Unknown
            && $this->dp_status !== DpStatus::Unknown
            && $this->electricity_status !== ReadinessUtilityStatus::Unknown
            && $this->water_status !== ReadinessUtilityStatus::Unknown
            && $this->consumer_status !== ReadinessIssueStatus::Unknown;
    }
}
