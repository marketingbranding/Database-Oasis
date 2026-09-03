<?php

namespace App\Filament\Resources\SalesCases\Actions;

use App\Actions\CancelDeveloperPpjbAction;
use App\Actions\CancelPsjbAction;
use App\Actions\CompleteCashPemberkasanAction;
use App\Actions\CreateAkadAction;
use App\Actions\CreateBastAction;
use App\Actions\CreateDeveloperPpjbAction;
use App\Actions\CreateDocumentSubmissionAction;
use App\Actions\CreatePsjbAction;
use App\Actions\RecordBankResponseAction;
use App\Actions\RecordBiCheckAction;
use App\Actions\ReissueDeveloperPpjbAction;
use App\Actions\ReissuePsjbAction;
use App\Actions\UpsertAkadReadinessAction;
use App\BankResponseType;
use App\BiCheckResult;
use App\DocumentSubmissionType;
use App\DpStatus;
use App\FinancingType;
use App\Models\Bank;
use App\Models\DocumentSubmission;
use App\Models\SalesCase;
use App\Models\User;
use App\ReadinessIssueStatus;
use App\ReadinessUtilityStatus;
use App\SalesCaseStage;
use App\SalesCaseStatus;
use Filament\Actions\Action;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Support\Icons\Heroicon;

/**
 * Workspace quick actions. Pure orchestration: every action invokes an
 * existing Phase 2-5 domain action; visibility only mirrors eligibility —
 * the domain actions remain authoritative for validation + authorization.
 */
class WorkspaceActions
{
    /** The single most likely next action for the case's current stage. */
    public static function primary(?SalesCase $record): ?Action
    {
        if ($record === null || $record->case_status !== SalesCaseStatus::Active) {
            return null;
        }

        return match ($record->current_stage) {
            SalesCaseStage::DataKonsumen, SalesCaseStage::BiChecking => self::addBiCheck(),
            SalesCaseStage::Psjb => self::createPsjb(),
            SalesCaseStage::Pemberkasan => $record->financing_type === FinancingType::Cash
                ? self::completeCashPemberkasan()
                : self::addPemberkasan(),
            SalesCaseStage::ProsesBank => self::recordBankResponse(),
            SalesCaseStage::PpjbDev => self::createPpjbDeveloper(),
            SalesCaseStage::Akad => self::createAkad(),
            SalesCaseStage::Bast => self::createBast(),
            SalesCaseStage::Completed => null,
        };
    }

    /**
     * All secondary actions, hidden when invalid. Never includes the primary.
     *
     * @param  array<string>  $exclude  action names to omit (e.g. the primary)
     * @return array<int, Action>
     */
    public static function secondary(SalesCase $record, array $exclude = []): array
    {
        $actions = [
            'addBiCheck' => self::addBiCheck(),
            'createPsjb' => self::createPsjb(),
            'reissuePsjb' => self::reissuePsjb(),
            'cancelPsjb' => self::cancelPsjb(),
            'addPemberkasan' => self::addPemberkasan(),
            'recordBankResponse' => self::recordBankResponse(),
            'completeCashPemberkasan' => self::completeCashPemberkasan(),
            'createPpjbDeveloper' => self::createPpjbDeveloper(),
            'reissuePpjbDeveloper' => self::reissuePpjbDeveloper(),
            'cancelPpjbDeveloper' => self::cancelPpjbDeveloper(),
            'createAkad' => self::createAkad(),
            'createBast' => self::createBast(),
            'updateReadiness' => self::updateReadiness(),
            'moveCase' => CaseWorkflowActions::move()->visible(
                fn (SalesCase $case): bool => $case->case_status === SalesCaseStatus::Active && ! $case->akad()->exists() && self::canUpdate($case)
            ),
            'markMundur' => CaseWorkflowActions::mundur()->visible(
                fn (SalesCase $case): bool => $case->case_status === SalesCaseStatus::Active && ! $case->akad()->exists() && self::canUpdate($case)
            ),
            'markRejected' => CaseWorkflowActions::reject()->visible(
                fn (SalesCase $case): bool => $case->case_status === SalesCaseStatus::Active && ! $case->akad()->exists() && self::canUpdate($case)
            ),
            'cancelCase' => CaseWorkflowActions::cancel()->visible(
                fn (SalesCase $case): bool => $case->case_status === SalesCaseStatus::Active && ! $case->akad()->exists() && self::canUpdate($case)
            ),
        ];

        return collect($actions)->reject(fn (Action $action, string $name): bool => in_array($name, $exclude, true))->values()->all();
    }

    private static function addBiCheck(): Action
    {
        return Action::make('addBiCheck')
            ->label('Add BI Check')
            ->icon(Heroicon::OutlinedShieldCheck)
            ->color('primary')
            ->visible(fn (SalesCase $case): bool => $case->case_status === SalesCaseStatus::Active)
            ->form([
                DatePicker::make('check_date')->label('Tanggal Cek')->default(now())->required(),
                Select::make('result')->label('Hasil')->options(BiCheckResult::class)->required(),
                Textarea::make('description')->label('Keterangan')->maxLength(1000),
            ])
            ->action(function (array $data, SalesCase $case): void {
                app(RecordBiCheckAction::class)->handle(self::user(), ['sales_case_id' => $case->id, ...$data]);
                self::notify('BI check dicatat.');
            });
    }

    private static function createPsjb(): Action
    {
        return Action::make('createPsjb')
            ->label('Create PSJB')
            ->icon(Heroicon::OutlinedDocumentText)
            ->color('primary')
            ->visible(fn (SalesCase $case): bool => $case->case_status === SalesCaseStatus::Active
                && $case->activePsjb()->doesntExist()
                && $case->akad()->doesntExist()
                && ($case->latestBiCheck()->first()?->result === BiCheckResult::Clear))
            ->form(self::psjbFields())
            ->action(function (array $data, SalesCase $case): void {
                app(CreatePsjbAction::class)->handle(self::user(), ['sales_case_id' => $case->id, ...$data]);
                self::notify('PSJB dibuat.');
            });
    }

    private static function reissuePsjb(): Action
    {
        return Action::make('reissuePsjb')
            ->label('Reissue PSJB')
            ->icon(Heroicon::OutlinedArrowPath)
            ->color('warning')
            ->visible(fn (SalesCase $case): bool => $case->case_status === SalesCaseStatus::Active
                && $case->activePsjb()->exists()
                && $case->akad()->doesntExist())
            ->form(self::psjbFields())
            ->action(function (array $data, SalesCase $case): void {
                app(ReissuePsjbAction::class)->handle(self::user(), $case, $data);
                self::notify('PSJB di-reissue.');
            });
    }

    private static function cancelPsjb(): Action
    {
        return Action::make('cancelPsjb')
            ->label('Cancel PSJB')
            ->icon(Heroicon::OutlinedMinusCircle)
            ->color('danger')
            ->requiresConfirmation()
            ->visible(fn (SalesCase $case): bool => $case->case_status === SalesCaseStatus::Active
                && $case->activePsjb !== null
                && $case->activePsjb->documentSubmissions()->doesntExist()
                && ! $case->current_stage->isBeyond(SalesCaseStage::Pemberkasan)
                && $case->akad()->doesntExist())
            ->action(function (SalesCase $case): void {
                app(CancelPsjbAction::class)->handle(self::user(), $case->activePsjb);
                self::notify('PSJB dibatalkan.');
            });
    }

    private static function addPemberkasan(): Action
    {
        return Action::make('addPemberkasan')
            ->label(fn (SalesCase $case): string => $case->documentSubmissions()->count() >= 2
                ? 'Submit ke Bank Lain'
                : 'Add Pemberkasan')
            ->icon(Heroicon::OutlinedFolderArrowDown)
            ->color('primary')
            ->visible(fn (SalesCase $case): bool => $case->case_status === SalesCaseStatus::Active
                && $case->financing_type === FinancingType::KprSubsidi
                && $case->activePsjb()->exists())
            ->form([
                Select::make('bank_id')->label('Bank')
                    ->options(fn (): array => Bank::query()->where('is_active', true)->orderBy('name')->pluck('name', 'id')->all())
                    ->searchable()->required(),
                DatePicker::make('submission_date')->label('Tanggal Submission')->default(now())->required(),
                TextInput::make('bank_branch')->label('Cabang Bank')->maxLength(255),
                Textarea::make('notes')->label('Catatan'),
            ])
            ->action(function (array $data, SalesCase $case): void {
                app(CreateDocumentSubmissionAction::class)->handle(self::user(), ['sales_case_id' => $case->id, ...$data]);
                self::notify('Pemberkasan dibuat.');
            });
    }

    private static function recordBankResponse(): Action
    {
        return Action::make('recordBankResponse')
            ->label('Record Bank Response')
            ->icon(Heroicon::OutlinedBuildingLibrary)
            ->color('primary')
            ->visible(fn (SalesCase $case): bool => $case->case_status === SalesCaseStatus::Active
                && $case->financing_type === FinancingType::KprSubsidi
                && $case->documentSubmissions()->exists())
            ->form([
                Select::make('document_submission_id')->label('Submission')
                    ->options(fn (SalesCase $case): array => $case->documentSubmissions()->with('bank')->get()
                        ->mapWithKeys(fn (DocumentSubmission $submission): array => [
                            $submission->id => sprintf('#%d — %s', $submission->sequence, $submission->bank->name),
                        ])->all())
                    ->searchable()->required(),
                Select::make('response_type')->label('Response')->options(BankResponseType::class)->live()->required(),
                DatePicker::make('response_date')->label('Tanggal Response')->default(now())->required(),
                TextInput::make('sp3k_number')->label('Nomor SP3K')
                    ->required(fn ($state, $get): bool => $get('response_type') === BankResponseType::Approved->value),
                DatePicker::make('sp3k_date')->label('Tanggal SP3K')
                    ->required(fn ($state, $get): bool => $get('response_type') === BankResponseType::Approved->value),
                TextInput::make('credit_limit')->label('Plafon Kredit')->numeric(),
                TextInput::make('tenor')->label('Tenor')->numeric(),
                Textarea::make('notes'),
            ])
            ->action(function (array $data, SalesCase $case): void {
                $submission = DocumentSubmission::findOrFail($data['document_submission_id']);
                app(RecordBankResponseAction::class)->handle(self::user(), [
                    'sales_case_id' => $case->id,
                    'bank_id' => $submission->bank_id,
                    ...$data,
                ]);
                self::notify('Bank response dicatat.');
            });
    }

    private static function completeCashPemberkasan(): Action
    {
        return Action::make('completeCashPemberkasan')
            ->label('Selesaikan Pemberkasan CASH')
            ->icon(Heroicon::OutlinedClipboardDocumentList)
            ->color('primary')
            ->requiresConfirmation()
            ->visible(fn (SalesCase $case): bool => $case->case_status === SalesCaseStatus::Active
                && $case->financing_type === FinancingType::Cash
                && $case->current_stage === SalesCaseStage::Pemberkasan
                && $case->activePsjb()->exists()
                && self::canUpdate($case))
            ->action(function (SalesCase $case): void {
                app(CompleteCashPemberkasanAction::class)->handle(self::user(), $case);
                self::notify('Pemberkasan CASH selesai. Lanjut ke PPJB Developer.');
            });
    }

    private static function createPpjbDeveloper(): Action
    {
        return Action::make('createPpjbDeveloper')
            ->label('Create PPJB Developer')
            ->icon(Heroicon::OutlinedDocumentCheck)
            ->color('primary')
            ->visible(fn (SalesCase $case): bool => $case->case_status === SalesCaseStatus::Active
                && $case->activeDeveloperPpjb()->doesntExist()
                && $case->akad()->doesntExist()
                && ($case->financing_type === FinancingType::Cash
                    ? $case->documentSubmissions()->where('type', DocumentSubmissionType::CashInternal->value)->exists()
                        && $case->current_stage->isBeyond(SalesCaseStage::Pemberkasan)
                    : $case->currentApprovedBankProcess()->exists()))
            ->form(self::ppjbFields())
            ->action(function (array $data, SalesCase $case): void {
                app(CreateDeveloperPpjbAction::class)->handle(self::user(), ['sales_case_id' => $case->id, ...$data]);
                self::notify('PPJB Developer dibuat.');
            });
    }

    private static function reissuePpjbDeveloper(): Action
    {
        return Action::make('reissuePpjbDeveloper')
            ->label('Reissue PPJB')
            ->icon(Heroicon::OutlinedArrowPath)
            ->color('warning')
            ->visible(fn (SalesCase $case): bool => $case->case_status === SalesCaseStatus::Active
                && $case->activeDeveloperPpjb()->exists()
                && $case->akad()->doesntExist())
            ->form(self::ppjbFields())
            ->action(function (array $data, SalesCase $case): void {
                app(ReissueDeveloperPpjbAction::class)->handle(self::user(), $case, $data);
                self::notify('PPJB Developer di-reissue.');
            });
    }

    private static function cancelPpjbDeveloper(): Action
    {
        return Action::make('cancelPpjbDeveloper')
            ->label('Cancel PPJB')
            ->icon(Heroicon::OutlinedMinusCircle)
            ->color('danger')
            ->requiresConfirmation()
            ->visible(fn (SalesCase $case): bool => $case->case_status === SalesCaseStatus::Active
                && $case->activeDeveloperPpjb()->exists()
                && $case->akad()->doesntExist())
            ->action(function (SalesCase $case): void {
                app(CancelDeveloperPpjbAction::class)->handle(self::user(), $case->activeDeveloperPpjb);
                self::notify('PPJB Developer dibatalkan.');
            });
    }

    private static function createAkad(): Action
    {
        return Action::make('createAkad')
            ->label('Create Akad')
            ->icon(Heroicon::OutlinedScale)
            ->color('primary')
            ->visible(fn (SalesCase $case): bool => $case->case_status === SalesCaseStatus::Active
                && $case->activeDeveloperPpjb()->exists()
                && $case->akad()->doesntExist())
            ->form([
                TextInput::make('document_number')->label('Nomor Akad'),
                DatePicker::make('akad_date')->label('Tanggal Akad')->default(now())->required(),
                TextInput::make('akad_quality')->label('Kualitas Akad'),
                Textarea::make('notes'),
            ])
            ->action(function (array $data, SalesCase $case): void {
                $data['developer_ppjb_id'] = $case->activeDeveloperPpjb()->firstOrFail()->id;
                app(CreateAkadAction::class)->handle(self::user(), ['sales_case_id' => $case->id, ...$data]);
                self::notify('Akad dibuat. Unit menjadi TERJUAL.');
            });
    }

    private static function createBast(): Action
    {
        return Action::make('createBast')
            ->label('Create BAST')
            ->icon(Heroicon::OutlinedKey)
            ->color('primary')
            ->visible(fn (SalesCase $case): bool => $case->case_status === SalesCaseStatus::Active
                && $case->akad()->exists()
                && $case->bast()->doesntExist())
            ->form([
                TextInput::make('bast_number')->label('Nomor BAST'),
                DatePicker::make('bast_date')->label('Tanggal BAST')->default(now())->required(),
                Textarea::make('notes'),
            ])
            ->action(function (array $data, SalesCase $case): void {
                $data['akad_id'] = $case->akad()->firstOrFail()->id;
                app(CreateBastAction::class)->handle(self::user(), ['sales_case_id' => $case->id, ...$data]);
                self::notify('BAST dibuat. Sales Case COMPLETED.');
            });
    }

    private static function updateReadiness(): Action
    {
        return Action::make('updateReadiness')
            ->label('Update Akad Readiness')
            ->icon(Heroicon::OutlinedClipboardDocumentCheck)
            ->visible(fn (SalesCase $case): bool => $case->akad()->doesntExist() && self::canUpdateReadiness($case))
            ->fillForm(fn (SalesCase $case): array => $case->akadReadiness?->only([
                'building_progress',
                'building_status',
                'dp_status',
                'electricity_status',
                'water_status',
                'consumer_status',
                'consumer_note',
                'notes',
            ]) ?? [
                'building_status' => ReadinessIssueStatus::Unknown,
                'dp_status' => DpStatus::Unknown,
                'electricity_status' => ReadinessUtilityStatus::Unknown,
                'water_status' => ReadinessUtilityStatus::Unknown,
                'consumer_status' => ReadinessIssueStatus::Unknown,
            ])
            ->schema([
                TextInput::make('building_progress')->label('Progress Bangunan')->numeric()->minValue(0)->maxValue(100)->suffix('%'),
                Select::make('building_status')->label('Status Bangunan')->options(ReadinessIssueStatus::class)->required(),
                Select::make('dp_status')->label('Status DP')->options(DpStatus::class)->required(),
                Select::make('electricity_status')->label('Listrik')->options(ReadinessUtilityStatus::class)->required(),
                Select::make('water_status')->label('Air')->options(ReadinessUtilityStatus::class)->required(),
                Select::make('consumer_status')->label('Kesiapan Konsumen')->options(ReadinessIssueStatus::class)->required(),
                Textarea::make('consumer_note')->label('Catatan Konsumen')->maxLength(2000),
                Textarea::make('notes')->label('Catatan')->maxLength(5000),
            ])
            ->action(function (array $data, SalesCase $case): void {
                app(UpsertAkadReadinessAction::class)->handle(self::user(), $case, $data);
                self::notify('Akad readiness diperbarui.');
            });
    }

    private static function canUpdateReadiness(SalesCase $case): bool
    {
        return User::current()?->can('update', $case) ?? false;
    }

    private static function canUpdate(SalesCase $case): bool
    {
        return User::current()?->can('update', $case) ?? false;
    }

    private static function user(): User
    {
        return User::current() ?? abort(403);
    }

    private static function notify(string $title): void
    {
        Notification::make()->title($title)->success()->send();
    }

    /** @return array<int, mixed> */
    private static function psjbFields(): array
    {
        return [
            DatePicker::make('psjb_date')->label('Tanggal PSJB')->default(now())->required(),
            TextInput::make('document_number')->label('Nomor Dokumen'),
            Select::make('coordinator_id')->label('Koordinator')
                ->options(fn (): array => User::query()->where('is_active', true)->orderBy('name')->limit(50)->pluck('name', 'id')->all())
                ->searchable(),
            Textarea::make('notes'),
        ];
    }

    /** @return array<int, mixed> */
    private static function ppjbFields(): array
    {
        return [
            TextInput::make('document_number')->label('Nomor PPJB'),
            DatePicker::make('document_date')->label('Tanggal PPJB')->default(now())->required(),
            Textarea::make('notes'),
        ];
    }
}
