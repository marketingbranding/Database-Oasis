<?php

namespace App\Models;

use App\FinancingType;
use App\SalesCaseStage;
use App\SalesCaseStatus;
use Carbon\CarbonInterface;
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

    /** @return HasMany<DocumentSubmission, $this> */
    public function documentSubmissions(): HasMany
    {
        return $this->hasMany(DocumentSubmission::class);
    }

    /** @return HasMany<BankProcess, $this> */
    public function bankProcesses(): HasMany
    {
        return $this->hasMany(BankProcess::class);
    }

    /** @return HasOne<BankProcess, $this> */
    public function currentApprovedBankProcess(): HasOne
    {
        return $this->hasOne(BankProcess::class)->where('is_authoritative', true);
    }

    /** @return HasMany<DeveloperPpjb, $this> */
    public function developerPpjbs(): HasMany
    {
        return $this->hasMany(DeveloperPpjb::class);
    }

    /** @return HasOne<DeveloperPpjb, $this> */
    public function activeDeveloperPpjb(): HasOne
    {
        return $this->hasOne(DeveloperPpjb::class)->where('status', 'ACTIVE');
    }

    /** @return HasOne<AkadRecord, $this> */
    public function akad(): HasOne
    {
        return $this->hasOne(AkadRecord::class);
    }

    /** @return HasOne<BastRecord, $this> */
    public function bast(): HasOne
    {
        return $this->hasOne(BastRecord::class);
    }

    /** @return HasMany<CaseNote, $this> */
    public function caseNotes(): HasMany
    {
        return $this->hasMany(CaseNote::class);
    }

    /** @return HasOne<BiCheck, $this> */
    public function latestBiCheck(): HasOne
    {
        return $this->hasOne(BiCheck::class)->ofMany([
            'check_date' => 'max',
            'id' => 'max',
        ]);
    }

    /** @return HasOne<DocumentSubmission, $this> */
    public function latestSubmission(): HasOne
    {
        return $this->hasOne(DocumentSubmission::class)->ofMany([
            'sequence' => 'max',
            'id' => 'max',
        ]);
    }

    /** @return HasOne<BankProcess, $this> */
    public function latestBankProcess(): HasOne
    {
        return $this->hasOne(BankProcess::class)->ofMany([
            'response_date' => 'max',
            'id' => 'max',
        ]);
    }

    /** @return HasOne<self, $this> */
    public function successorCase(): HasOne
    {
        return $this->hasOne(self::class, 'previous_case_id');
    }

    /**
     * Days spent in the current stage, deterministic from business dates.
     *
     * Stage entry-date hierarchy:
     *   DATA_KONSUMEN -> booking_date (fallback created_at)
     *   BI_CHECKING   -> max bi_checks.check_date (any result; while at this
     *                    stage the latest BI is by definition non-CLEAR)
     *   PSJB          -> max bi_checks.check_date (invariant: latest BI is the
     *                    CLEAR check that advanced the case; a later non-CLEAR
     *                    check would have regressed the stage)
     *   PEMBERKASAN   -> max psjbs.psjb_date
     *   PROSES_BANK   -> max document_submissions.submission_date
     *   PPJB_DEV      -> authoritative approval response_date (KPR); for CASH
     *                    (no record) -> max psjbs.psjb_date
     *   AKAD          -> max developer_ppjbs.document_date
     *   BAST          -> akad.akad_date
     *   COMPLETED     -> bast.bast_date (fallback closed_at)
     *
     * Falls back to created_at of the case when the expected business record
     * is missing. Prefers eager aggregate attributes (from withMax()) when
     * loaded to avoid N+1 in table views.
     */
    public function daysInCurrentStage(): ?int
    {
        $date = match ($this->current_stage->value) {
            SalesCaseStage::DataKonsumen->value => $this->booking_date ?? $this->created_at,
            SalesCaseStage::BiChecking->value, SalesCaseStage::Psjb->value => $this->aggregateDate('biChecks', 'check_date'),
            SalesCaseStage::Pemberkasan->value => $this->aggregateDate('psjbs', 'psjb_date'),
            SalesCaseStage::ProsesBank->value => $this->aggregateDate('documentSubmissions', 'submission_date'),
            SalesCaseStage::PpjbDev->value => $this->eagerMaxDate('currentApprovedBankProcess', 'response_date')
                ?? $this->aggregateDate('psjbs', 'psjb_date'),
            SalesCaseStage::Akad->value => $this->aggregateDate('developerPpjbs', 'document_date'),
            SalesCaseStage::Bast->value => $this->eagerMaxDate('akad', 'akad_date'),
            SalesCaseStage::Completed->value => $this->eagerMaxDate('bast', 'bast_date') ?? $this->closed_at,
        };

        if ($date === null) {
            return null;
        }

        $date = $this->toCarbon($date);

        return abs((int) round(now()->startOfDay()->diffInDays($date->startOfDay())));
    }

    /**
     * @return array<string, 'done'|'current'|'upcoming'> keyed by stage value
     */
    public function stageProgress(): array
    {
        $current = $this->current_stage;

        $evidence = [
            SalesCaseStage::DataKonsumen->value => true,
            SalesCaseStage::BiChecking->value => $this->biChecks()->exists(),
            SalesCaseStage::Psjb->value => $this->psjbs()->exists(),
            SalesCaseStage::Pemberkasan->value => $this->documentSubmissions()->exists(),
            SalesCaseStage::ProsesBank->value => $this->bankProcesses()->exists(),
            SalesCaseStage::PpjbDev->value => $this->developerPpjbs()->exists(),
            SalesCaseStage::Akad->value => $this->akad()->exists(),
            SalesCaseStage::Bast->value => $this->bast()->exists(),
            SalesCaseStage::Completed->value => $this->case_status === SalesCaseStatus::Completed,
        ];

        $progress = [];
        foreach (SalesCaseStage::cases() as $stage) {
            $progress[$stage->value] = match (true) {
                $stage === $current => 'current',
                $evidence[$stage->value] => 'done',
                default => 'upcoming',
            };
        }

        return $progress;
    }

    private function aggregateDate(string $relation, string $column): ?CarbonInterface
    {
        return $this->eagerMaxDate($relation, $column)
            ?? $this->toCarbon($this->{$relation}()->max($column));
    }

    private function eagerMaxDate(string $relation, string $column): ?CarbonInterface
    {
        $key = $relation.'_max_'.$column;

        if (array_key_exists($key, $this->attributes)) {
            return $this->toCarbon($this->attributes[$key]);
        }

        return null;
    }

    private function toCarbon(mixed $value): ?CarbonInterface
    {
        if ($value === null) {
            return null;
        }

        return $value instanceof CarbonInterface ? $value : $this->asDateTime($value);
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
