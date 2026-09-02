<?php

namespace App\Models;

use App\FinancingType;
use App\SalesCaseStage;
use App\SalesCaseStatus;
use Database\Factories\SalesCaseFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;

#[Fillable(['consumer_id', 'unit_id', 'project_id', 'branch_id', 'financing_type', 'booking_date', 'source', 'current_stage', 'case_status', 'previous_case_id', 'transfer_reason', 'sales_pic_id', 'coordinator_id', 'closed_at', 'closed_reason', 'created_by'])]
class SalesCase extends Model
{
    /** @use HasFactory<SalesCaseFactory> */
    use HasFactory, SoftDeletes;

    protected function casts(): array
    {
        return [
            'financing_type' => FinancingType::class,
            'booking_date' => 'date',
            'current_stage' => SalesCaseStage::class,
            'case_status' => SalesCaseStatus::class,
            'closed_at' => 'datetime',
        ];
    }

    /**
     * @param  Builder<self>  $query
     * @return Builder<self>
     */
    public function scopeActive(Builder $query): Builder
    {
        return $query->where('case_status', SalesCaseStatus::Active->value);
    }

    /** @return BelongsTo<Consumer, $this> */
    public function consumer(): BelongsTo
    {
        return $this->belongsTo(Consumer::class);
    }

    /** @return BelongsTo<Unit, $this> */
    public function unit(): BelongsTo
    {
        return $this->belongsTo(Unit::class);
    }

    /** @return BelongsTo<Project, $this> */
    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }

    /** @return BelongsTo<Branch, $this> */
    public function branch(): BelongsTo
    {
        return $this->belongsTo(Branch::class);
    }

    /** @return BelongsTo<User, $this> */
    public function salesPic(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /** @return BelongsTo<User, $this> */
    public function coordinator(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /** @return BelongsTo<User, $this> */
    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /** @return BelongsTo<self, $this> */
    public function previousCase(): BelongsTo
    {
        return $this->belongsTo(self::class, 'previous_case_id');
    }

    /** @return HasMany<BiCheck, $this> */
    public function biChecks(): HasMany
    {
        return $this->hasMany(BiCheck::class);
    }

    /** @return HasMany<Psjb, $this> */
    public function psjbs(): HasMany
    {
        return $this->hasMany(Psjb::class);
    }

    /** @return HasOne<Psjb, $this> */
    public function activePsjb(): HasOne
    {
        return $this->hasOne(Psjb::class)->where('status', 'ACTIVE');
    }

    /**
     * Move the operational stage forward only. Centralized so no caller can
     * accidentally regress a case that has legitimately progressed.
     */
    public function advanceStageTo(SalesCaseStage $stage): bool
    {
        if (! $stage->isBeyond($this->current_stage)) {
            return false;
        }

        $this->update(['current_stage' => $stage]);
        $this->refresh();

        return true;
    }

    /**
     * ACTIVE sales cases pickable in transaction forms, scoped to the user's
     * branch, searchable by consumer name/NIK or unit code.
     *
     * @return Builder<self>
     */
    public static function pickableActiveCases(?User $user, ?string $search = null): Builder
    {
        return self::query()
            ->where('case_status', SalesCaseStatus::Active->value)
            ->with(['consumer', 'unit'])
            ->when($user?->isBranchScoped(), fn (Builder $query) => $query->where('branch_id', $user->branch_id))
            ->when(filled($search), fn (Builder $query) => $query->where(
                fn (Builder $query) => $query
                    ->whereHas('consumer', fn (Builder $query) => $query
                        ->where('name', 'like', "%{$search}%")
                        ->orWhere('nik', 'like', "%{$search}%"))
                    ->orWhereHas('unit', fn (Builder $query) => $query->where('unit_code', 'like', "%{$search}%")),
            ));
    }
}
