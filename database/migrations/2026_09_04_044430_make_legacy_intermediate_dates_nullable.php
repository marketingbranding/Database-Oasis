<?php

use App\Support\Database\PartialUniqueGuard;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Make business dates on legacy-tolerant intermediate entities nullable so
     * missing historical dates can be stored as NULL + legacy_date_missing.
     *
     * Guard handling is driver-aware: PostgreSQL/SQLite keep partial unique
     * indexes (dropped first so SQLite's ALTER TABLE rebuild cannot degrade
     * them), while MariaDB/MySQL relies on the nullable generated columns
     * created with the tables. Legacy-date rules are enforced with CHECK
     * constraints on PostgreSQL, SQLite triggers on SQLite, and SIGNAL
     * triggers on MariaDB/MySQL.
     */
    public function up(): void
    {
        PartialUniqueGuard::dropIndex('psjbs_sales_case_active_unique', 'psjbs');
        PartialUniqueGuard::dropIndex('bank_processes_authoritative_approval_unique', 'bank_processes');
        PartialUniqueGuard::dropIndex('developer_ppjbs_sales_case_active_unique', 'developer_ppjbs');

        $this->makeNullable(true);

        PartialUniqueGuard::createPartialUnique('psjbs', 'psjbs_sales_case_active_unique', 'sales_case_id', "status = 'ACTIVE'");
        PartialUniqueGuard::createPartialUnique('bank_processes', 'bank_processes_authoritative_approval_unique', 'sales_case_id', 'is_authoritative = true');
        PartialUniqueGuard::createPartialUnique('developer_ppjbs', 'developer_ppjbs_sales_case_active_unique', 'sales_case_id', "status = 'ACTIVE'");

        $this->enforceLegacyDateRule('bi_checks', 'check_date');
        $this->enforceLegacyDateRule('psjbs', 'psjb_date');
        $this->enforceLegacyDateRule('document_submissions', 'submission_date');
        $this->enforceLegacyDateRule('bank_processes', 'response_date');
        $this->enforceLegacyDateRule('developer_ppjbs', 'document_date');
    }

    public function down(): void
    {
        $this->dropLegacyDateRule('bi_checks', 'check_date');
        $this->dropLegacyDateRule('psjbs', 'psjb_date');
        $this->dropLegacyDateRule('document_submissions', 'submission_date');
        $this->dropLegacyDateRule('bank_processes', 'response_date');
        $this->dropLegacyDateRule('developer_ppjbs', 'document_date');

        PartialUniqueGuard::dropIndex('psjbs_sales_case_active_unique', 'psjbs');
        PartialUniqueGuard::dropIndex('bank_processes_authoritative_approval_unique', 'bank_processes');
        PartialUniqueGuard::dropIndex('developer_ppjbs_sales_case_active_unique', 'developer_ppjbs');

        $this->makeNullable(false);

        PartialUniqueGuard::createPartialUnique('psjbs', 'psjbs_sales_case_active_unique', 'sales_case_id', "status = 'ACTIVE'");
        PartialUniqueGuard::createPartialUnique('bank_processes', 'bank_processes_authoritative_approval_unique', 'sales_case_id', 'is_authoritative = true');
        PartialUniqueGuard::createPartialUnique('developer_ppjbs', 'developer_ppjbs_sales_case_active_unique', 'sales_case_id', "status = 'ACTIVE'");
    }

    /**
     * @param  array<string, string>  $columns  table => date column
     */
    private function columns(): array
    {
        return [
            'bi_checks' => 'check_date',
            'psjbs' => 'psjb_date',
            'document_submissions' => 'submission_date',
            'bank_processes' => 'response_date',
            'developer_ppjbs' => 'document_date',
        ];
    }

    private function makeNullable(bool $nullable): void
    {
        if (PartialUniqueGuard::isMysqlFamily()) {
            // Blueprint::change() needs doctrine/dbal, which is not installed.
            foreach ($this->columns() as $table => $column) {
                PartialUniqueGuard::modifyNullable($table, $column, 'DATE', $nullable);
            }

            return;
        }

        foreach ($this->columns() as $table => $column) {
            Schema::table($table, fn (Blueprint $table) => $table->date($column)->nullable($nullable)->change());
        }
    }

    private function enforceLegacyDateRule(string $table, string $column): void
    {
        $name = "{$table}_{$column}_legacy_missing_check";

        if (DB::getDriverName() === 'pgsql') {
            DB::statement("ALTER TABLE {$table} ADD CONSTRAINT {$name} CHECK ({$column} IS NOT NULL OR (is_legacy_import = true AND legacy_date_missing = true))");

            return;
        }

        if (PartialUniqueGuard::isMysqlFamily()) {
            DB::unprepared("CREATE TRIGGER {$name}_insert BEFORE INSERT ON {$table} FOR EACH ROW BEGIN IF NEW.{$column} IS NULL AND NOT (NEW.is_legacy_import = 1 AND NEW.legacy_date_missing = 1) THEN SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = '{$name}'; END IF; END");
            DB::unprepared("CREATE TRIGGER {$name}_update BEFORE UPDATE ON {$table} FOR EACH ROW BEGIN IF NEW.{$column} IS NULL AND NOT (NEW.is_legacy_import = 1 AND NEW.legacy_date_missing = 1) THEN SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = '{$name}'; END IF; END");

            return;
        }

        DB::statement("CREATE TRIGGER {$name}_insert BEFORE INSERT ON {$table} WHEN NEW.{$column} IS NULL AND NOT (NEW.is_legacy_import = 1 AND NEW.legacy_date_missing = 1) BEGIN SELECT RAISE(ABORT, '{$name}'); END");
        DB::statement("CREATE TRIGGER {$name}_update BEFORE UPDATE ON {$table} WHEN NEW.{$column} IS NULL AND NOT (NEW.is_legacy_import = 1 AND NEW.legacy_date_missing = 1) BEGIN SELECT RAISE(ABORT, '{$name}'); END");
    }

    private function dropLegacyDateRule(string $table, string $column): void
    {
        $name = "{$table}_{$column}_legacy_missing_check";

        if (DB::getDriverName() === 'pgsql') {
            DB::statement("ALTER TABLE {$table} DROP CONSTRAINT IF EXISTS {$name}");

            return;
        }

        if (PartialUniqueGuard::isMysqlFamily()) {
            DB::statement("DROP TRIGGER IF EXISTS {$name}_insert");
            DB::statement("DROP TRIGGER IF EXISTS {$name}_update");

            return;
        }

        DB::statement("DROP TRIGGER IF EXISTS {$name}_insert");
        DB::statement("DROP TRIGGER IF EXISTS {$name}_update");
    }
};
