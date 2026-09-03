<?php

namespace Database\Seeders;

use App\Actions\CompleteCashPemberkasanAction;
use App\Actions\CreateAkadAction;
use App\Actions\CreateBastAction;
use App\Actions\CreateDeveloperPpjbAction;
use App\Actions\CreateDocumentSubmissionAction;
use App\Actions\CreatePsjbAction;
use App\Actions\CreateSalesCaseAction;
use App\Actions\MarkSalesCaseMundurAction;
use App\Actions\MoveSalesCaseUnitAction;
use App\Actions\RecordBankResponseAction;
use App\Actions\RecordBiCheckAction;
use App\Actions\UpsertAkadReadinessAction;
use App\BankResponseType;
use App\BiCheckResult;
use App\DpStatus;
use App\Models\AkadRecord;
use App\Models\AkadTarget;
use App\Models\Bank;
use App\Models\BankProcess;
use App\Models\BastRecord;
use App\Models\Branch;
use App\Models\Consumer;
use App\Models\DeveloperPpjb;
use App\Models\DocumentSubmission;
use App\Models\Project;
use App\Models\Psjb;
use App\Models\SalesCase;
use App\Models\Unit;
use App\Models\User;
use App\ReadinessIssueStatus;
use App\ReadinessUtilityStatus;
use App\UserRole;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Hash;

/**
 * Development/demo seed data for manual UAT (Phase 7.5).
 *
 * Never runs automatically: DatabaseSeeder does not call it and the guard
 * below aborts in production. Run manually with:
 *   php artisan db:seed --class=Database\\Seeders\\UatDemoSeeder
 */
class UatDemoSeeder extends Seeder
{
    private User $hq;

    private User $jeparaAdmin;

    private User $semarangAdmin;

    private Branch $jepara;

    private Branch $semarang;

    public function run(): void
    {
        if (App::environment('production')) {
            throw new \RuntimeException('UatDemoSeeder must never run in production.');
        }

        if (User::query()->where('email', 'super@uat.test')->exists()) {
            $this->command->warn('UAT demo data already seeded. Run migrate:fresh first for a clean slate.');

            return;
        }

        $this->seedAccounts();
        $this->seedScenarioKprNormalCompleted();
        $this->seedScenarioMultipleBankNoKendala();
        $this->seedScenarioCashCompleted();
        $this->seedScenarioPindahKavling();
        $this->seedScenarioMundurUnitReuse();
        $this->seedScenarioSp3kMultipleKendala();
        $this->seedScenarioSp3kAllUnknown();
        $this->seedScenarioSp3kNoReadinessRow();
        $this->seedScenarioDuplicateNumbers();
        $this->seedScenarioSemarangIsolation();

        $this->command->info('UAT demo data seeded. Credentials in docs/UAT_GUIDE.md (password: password).');
    }

    private function seedAccounts(): void
    {
        $this->jepara = Branch::query()->firstOrCreate(['code' => 'JPR'], [
            'name' => 'Cabang Jepara', 'city' => 'Jepara', 'province' => 'Jawa Tengah', 'is_active' => true,
        ]);
        $this->semarang = Branch::query()->firstOrCreate(['code' => 'SMG'], [
            'name' => 'Cabang Semarang', 'city' => 'Semarang', 'province' => 'Jawa Tengah', 'is_active' => true,
        ]);

        foreach ([['BTN', 'Bank BTN'], ['BRI', 'Bank BRI'], ['BCA', 'Bank BCA']] as [$code, $name]) {
            Bank::query()->firstOrCreate(['code' => $code], ['name' => $name, 'is_active' => true]);
        }

        $this->makeUser('super@uat.test', 'Super Admin UAT', UserRole::SuperAdmin);
        $this->hq = $this->makeUser('hq@uat.test', 'HQ Admin UAT', UserRole::HqAdmin);
        $this->jeparaAdmin = $this->makeUser('jepara.admin@uat.test', 'Admin Jepara', UserRole::BranchAdmin, $this->jepara);
        $this->makeUser('jepara.manager@uat.test', 'Manager Jepara', UserRole::BranchManager, $this->jepara);
        $this->semarangAdmin = $this->makeUser('semarang.admin@uat.test', 'Admin Semarang', UserRole::BranchAdmin, $this->semarang);
        $this->makeUser('management@uat.test', 'Management UAT', UserRole::Management);
        $this->makeUser('auditor@uat.test', 'Auditor UAT', UserRole::Auditor);

        AkadTarget::query()->firstOrCreate([
            'branch_id' => $this->jepara->id, 'project_id' => null, 'period_month' => now()->startOfMonth()->toDateString(),
        ], ['target' => 10, 'created_by' => $this->hq->id, 'updated_by' => $this->hq->id]);
        AkadTarget::query()->firstOrCreate([
            'branch_id' => $this->semarang->id, 'project_id' => null, 'period_month' => now()->startOfMonth()->toDateString(),
        ], ['target' => 5, 'created_by' => $this->hq->id, 'updated_by' => $this->hq->id]);
    }

    private function makeUser(string $email, string $name, UserRole $role, ?Branch $branch = null): User
    {
        $user = User::query()->firstOrCreate(['email' => $email], [
            'name' => $name,
            'password' => Hash::make('password'),
            'branch_id' => $branch?->id,
            'email_verified_at' => now(),
            'is_active' => true,
        ]);
        $user->assignRole($role);

        return $user;
    }

    private function unit(Project $project, string $code): Unit
    {
        return Unit::query()->firstOrCreate(['project_id' => $project->id, 'unit_code' => $code], [
            'block' => substr($code, 0, 1),
            'number' => substr($code, 2),
            'status' => 'TERSEDIA',
        ]);
    }

    private function project(Branch $branch, string $code, string $name): Project
    {
        return Project::query()->firstOrCreate(['branch_id' => $branch->id, 'code' => $code], [
            'name' => $name, 'location' => $branch->city, 'status' => 'AKTIF',
        ]);
    }

    private function caseOn(Unit $unit, string $consumerName, string $financing = 'KPR_SUBSIDI'): SalesCase
    {
        return app(CreateSalesCaseAction::class)->handle($this->hq, [
            'unit_id' => $unit->id,
            'consumer_id' => Consumer::factory()->create(['name' => $consumerName])->id,
            'financing_type' => $financing,
            'booking_date' => now()->startOfMonth()->toDateString(),
        ]);
    }

    private function bi(SalesCase $case, ?User $actor = null): void
    {
        app(RecordBiCheckAction::class)->handle($actor ?? $this->jeparaAdmin, [
            'sales_case_id' => $case->id, 'check_date' => now()->toDateString(), 'result' => BiCheckResult::Clear,
        ]);
    }

    private function psjb(SalesCase $case, ?string $number = null, ?User $actor = null): Psjb
    {
        return app(CreatePsjbAction::class)->handle($actor ?? $this->jeparaAdmin, [
            'sales_case_id' => $case->id, 'psjb_date' => now()->toDateString(), 'document_number' => $number,
        ]);
    }

    private function submit(SalesCase $case, Bank $bank, ?User $actor = null): DocumentSubmission
    {
        return app(CreateDocumentSubmissionAction::class)->handle($actor ?? $this->jeparaAdmin, [
            'sales_case_id' => $case->id, 'bank_id' => $bank->id, 'submission_date' => now()->toDateString(),
        ]);
    }

    /** @param array<string, mixed> $extra */
    private function respond(SalesCase $case, DocumentSubmission $submission, BankResponseType $type, array $extra = [], ?User $actor = null): BankProcess
    {
        return app(RecordBankResponseAction::class)->handle($actor ?? $this->jeparaAdmin, [
            'sales_case_id' => $case->id,
            'document_submission_id' => $submission->id,
            'bank_id' => $submission->bank_id,
            'response_type' => $type,
            'response_date' => now()->toDateString(),
            ...$extra,
        ]);
    }

    private function ppjb(SalesCase $case, ?string $number = null, ?User $actor = null): DeveloperPpjb
    {
        return app(CreateDeveloperPpjbAction::class)->handle($actor ?? $this->jeparaAdmin, [
            'sales_case_id' => $case->id, 'document_date' => now()->toDateString(), 'document_number' => $number,
        ]);
    }

    private function akad(SalesCase $case, ?DeveloperPpjb $ppjb = null, ?string $number = null, ?User $actor = null): AkadRecord
    {
        return app(CreateAkadAction::class)->handle($actor ?? $this->jeparaAdmin, [
            'sales_case_id' => $case->id,
            'developer_ppjb_id' => $ppjb !== null ? $ppjb->id : $case->activeDeveloperPpjb()->firstOrFail()->id,
            'akad_date' => now()->toDateString(),
            'document_number' => $number,
        ]);
    }

    private function bast(SalesCase $case, AkadRecord $akad, ?User $actor = null): BastRecord
    {
        return app(CreateBastAction::class)->handle($actor ?? $this->jeparaAdmin, [
            'sales_case_id' => $case->id, 'akad_id' => $akad->id, 'bast_date' => now()->toDateString(),
        ]);
    }

    /** @param array<string, mixed> $attributes */
    private function readiness(SalesCase $case, array $attributes, ?User $actor = null): void
    {
        app(UpsertAkadReadinessAction::class)->handle($actor ?? $this->jeparaAdmin, $case, $attributes);
    }

    /** @return array<string, mixed> */
    private function readinessClear(): array
    {
        return [
            'building_progress' => 100,
            'building_status' => ReadinessIssueStatus::Clear,
            'dp_status' => DpStatus::Complete,
            'electricity_status' => ReadinessUtilityStatus::Installed,
            'water_status' => ReadinessUtilityStatus::Installed,
            'consumer_status' => ReadinessIssueStatus::Clear,
        ];
    }

    private function seedScenarioKprNormalCompleted(): void
    {
        $marison = $this->project($this->jepara, 'MRA', 'Marison Regency A');
        $case = $this->caseOn($this->unit($marison, 'A-01'), 'Budi Santoso');
        $this->bi($case);
        $this->psjb($case);
        $submission = $this->submit($case, Bank::query()->where('code', 'BCA')->firstOrFail());
        $this->respond($case, $submission, BankResponseType::Process);
        $this->respond($case, $submission, BankResponseType::Approved, [
            'sp3k_number' => 'SP3K-UAT-001', 'sp3k_date' => now()->toDateString(),
        ]);
        $this->akad($case, $this->ppjb($case), 'AKAD-UAT-001');
        $this->bast($case, $case->akad()->firstOrFail());
    }

    private function seedScenarioMultipleBankNoKendala(): void
    {
        $marison = $this->project($this->jepara, 'MRA', 'Marison Regency A');
        $case = $this->caseOn($this->unit($marison, 'A-02'), 'Citra Lestari');
        $this->bi($case);
        $this->psjb($case);

        $btn = $this->submit($case, Bank::query()->where('code', 'BTN')->firstOrFail());
        $this->respond($case, $btn, BankResponseType::Rejected);

        $bri = $this->submit($case, Bank::query()->where('code', 'BRI')->firstOrFail());
        $this->respond($case, $bri, BankResponseType::Approved, [
            'sp3k_number' => 'SP3K-UAT-002', 'sp3k_date' => now()->subDays(3)->toDateString(),
        ]);

        $this->readiness($case, $this->readinessClear());
    }

    private function seedScenarioCashCompleted(): void
    {
        $marison = $this->project($this->jepara, 'MRA', 'Marison Regency A');
        $case = $this->caseOn($this->unit($marison, 'A-03'), 'Dedi Pratama', 'CASH');
        $this->psjb($case);
        app(CompleteCashPemberkasanAction::class)->handle($this->jeparaAdmin, $case);
        $this->akad($case, $this->ppjb($case), 'AKAD-UAT-CASH');
        $this->bast($case, $case->akad()->firstOrFail());
    }

    private function seedScenarioPindahKavling(): void
    {
        $marisonB = $this->project($this->jepara, 'MRB', 'Marison Regency B');
        $unitK20 = $this->unit($marisonB, 'K-20');
        $unitK15 = $this->unit($marisonB, 'K-15');

        $case = $this->caseOn($unitK20, 'Eko Prasetyo');
        $this->bi($case);

        app(MoveSalesCaseUnitAction::class)->handle($this->jeparaAdmin, $case, $unitK15->id, 'Pindah ke kavling K-15');
    }

    private function seedScenarioMundurUnitReuse(): void
    {
        $marison = $this->project($this->jepara, 'MRA', 'Marison Regency A');
        $unit = $this->unit($marison, 'A-05');

        $old = $this->caseOn($unit, 'Fajar Nugroho');
        app(MarkSalesCaseMundurAction::class)->handle($this->jeparaAdmin, $old, 'Konsumen mundur: penghasilan tidak memenuhi');

        $new = $this->caseOn($unit, 'Gilang Ramadhan');
        $this->bi($new);
    }

    private function seedScenarioSp3kMultipleKendala(): void
    {
        $marison = $this->project($this->jepara, 'MRA', 'Marison Regency A');
        $case = $this->caseOn($this->unit($marison, 'A-06'), 'Hana Wijaya');
        $this->bi($case);
        $this->psjb($case);
        $submission = $this->submit($case, Bank::query()->where('code', 'BRI')->firstOrFail());
        $this->respond($case, $submission, BankResponseType::Approved, [
            'sp3k_number' => 'SP3K-UAT-003', 'sp3k_date' => now()->subDays(20)->toDateString(),
        ]);

        $this->readiness($case, [
            'building_progress' => 55,
            'building_status' => ReadinessIssueStatus::Issue,
            'dp_status' => DpStatus::Incomplete,
            'electricity_status' => ReadinessUtilityStatus::NotInstalled,
            'water_status' => ReadinessUtilityStatus::Installed,
            'consumer_status' => ReadinessIssueStatus::Issue,
            'consumer_note' => 'Konsumen di luar kota, sulit dijadwalkan untuk akad',
        ]);
    }

    private function seedScenarioSp3kAllUnknown(): void
    {
        $marison = $this->project($this->jepara, 'MRA', 'Marison Regency A');
        $case = $this->caseOn($this->unit($marison, 'A-07'), 'Indra Kusuma');
        $this->bi($case);
        $this->psjb($case);
        $submission = $this->submit($case, Bank::query()->where('code', 'BTN')->firstOrFail());
        $this->respond($case, $submission, BankResponseType::Approved, [
            'sp3k_number' => 'SP3K-UAT-004', 'sp3k_date' => now()->subDays(40)->toDateString(),
        ]);

        $this->readiness($case, [
            'building_status' => ReadinessIssueStatus::Unknown,
            'dp_status' => DpStatus::Unknown,
            'electricity_status' => ReadinessUtilityStatus::Unknown,
            'water_status' => ReadinessUtilityStatus::Unknown,
            'consumer_status' => ReadinessIssueStatus::Unknown,
        ]);
    }

    private function seedScenarioSp3kNoReadinessRow(): void
    {
        $marison = $this->project($this->jepara, 'MRA', 'Marison Regency A');
        $case = $this->caseOn($this->unit($marison, 'A-08'), 'Joko Susilo');
        $this->bi($case);
        $this->psjb($case);
        $submission = $this->submit($case, Bank::query()->where('code', 'BCA')->firstOrFail());
        $this->respond($case, $submission, BankResponseType::Approved, [
            'sp3k_number' => 'SP3K-UAT-005', 'sp3k_date' => now()->subDays(10)->toDateString(),
        ]);
    }

    private function seedScenarioDuplicateNumbers(): void
    {
        $marison = $this->project($this->jepara, 'MRA', 'Marison Regency A');

        $kusnadi = $this->caseOn($this->unit($marison, 'A-09'), 'Kusnadi Hartono');
        $this->bi($kusnadi);
        $this->psjb($kusnadi, 'PSJB-UAT-DUP');
        $kusnadiSubmission = $this->submit($kusnadi, Bank::query()->where('code', 'BRI')->firstOrFail());
        $this->respond($kusnadi, $kusnadiSubmission, BankResponseType::Approved, [
            'sp3k_number' => 'SP3K-UAT-DUP', 'sp3k_date' => now()->toDateString(),
        ]);

        $lina = $this->caseOn($this->unit($marison, 'A-10'), 'Lina Marlina');
        $this->bi($lina);
        $this->psjb($lina, 'PSJB-UAT-DUP');
        $linaSubmission = $this->submit($lina, Bank::query()->where('code', 'BCA')->firstOrFail());
        $this->respond($lina, $linaSubmission, BankResponseType::Approved, [
            'sp3k_number' => 'SP3K-UAT-DUP', 'sp3k_date' => now()->toDateString(),
        ]);

        $this->ppjb($kusnadi, 'PPJB-UAT-DUP');
        $this->ppjb($lina, 'PPJB-UAT-DUP');
    }

    private function seedScenarioSemarangIsolation(): void
    {
        $project = $this->project($this->semarang, 'SMA', 'Semarang Indah');
        $case = $this->caseOn($this->unit($project, 'S-01'), 'Mulyadi Setiawan');
        $this->bi($case, $this->semarangAdmin);
        $this->psjb($case, null, $this->semarangAdmin);
        $submission = $this->submit($case, Bank::query()->where('code', 'BTN')->firstOrFail(), $this->semarangAdmin);
        $this->respond($case, $submission, BankResponseType::Process, [], $this->semarangAdmin);
    }
}
