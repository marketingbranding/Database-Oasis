<?php

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
     * SQLite's ALTER TABLE rebuild would otherwise preserve the pre-existing
     * partial unique indexes as FULL column-unique indexes (or drop them).
     * To guarantee partial semantics survive on both PostgreSQL and SQLite, we
     * explicitly drop them first, run the column change, then recreate them
     * with the exact same WHERE predicates. This never silently degrades an
     * active-unique guard into a full row-unique or loses it.
     */
    public function up(): void
    {
        DB::statement('DROP INDEX IF EXISTS psjbs_sales_case_active_unique');
        DB::statement('DROP INDEX IF EXISTS bank_processes_authoritative_approval_unique');
        DB::statement('DROP INDEX IF EXISTS developer_ppjbs_sales_case_active_unique');

        Schema::table('bi_checks', fn (Blueprint $table) => $table->date('check_date')->nullable()->change());
        Schema::table('psjbs', fn (Blueprint $table) => $table->date('psjb_date')->nullable()->change());
        Schema::table('document_submissions', fn (Blueprint $table) => $table->date('submission_date')->nullable()->change());
        Schema::table('bank_processes', fn (Blueprint $table) => $table->date('response_date')->nullable()->change());
        Schema::table('developer_ppjbs', fn (Blueprint $table) => $table->date('document_date')->nullable()->change());

        DB::statement("CREATE UNIQUE INDEX psjbs_sales_case_active_unique ON psjbs (sales_case_id) WHERE status = 'ACTIVE'");
        DB::statement('CREATE UNIQUE INDEX bank_processes_authoritative_approval_unique ON bank_processes (sales_case_id) WHERE is_authoritative = true');
        DB::statement("CREATE UNIQUE INDEX developer_ppjbs_sales_case_active_unique ON developer_ppjbs (sales_case_id) WHERE status = 'ACTIVE'");

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

        DB::statement('DROP INDEX IF EXISTS psjbs_sales_case_active_unique');
        DB::statement('DROP INDEX IF EXISTS bank_processes_authoritative_approval_unique');
        DB::statement('DROP INDEX IF EXISTS developer_ppjbs_sales_case_active_unique');

        Schema::table('bi_checks', fn (Blueprint $table) => $table->date('check_date')->nullable(false)->change());
        Schema::table('psjbs', fn (Blueprint $table) => $table->date('psjb_date')->nullable(false)->change());
        Schema::table('document_submissions', fn (Blueprint $table) => $table->date('submission_date')->nullable(false)->change());
        Schema::table('bank_processes', fn (Blueprint $table) => $table->date('response_date')->nullable(false)->change());
        Schema::table('developer_ppjbs', fn (Blueprint $table) => $table->date('document_date')->nullable(false)->change());

        DB::statement("CREATE UNIQUE INDEX psjbs_sales_case_active_unique ON psjbs (sales_case_id) WHERE status = 'ACTIVE'");
        DB::statement('CREATE UNIQUE INDEX bank_processes_authoritative_approval_unique ON bank_processes (sales_case_id) WHERE is_authoritative = true');
        DB::statement("CREATE UNIQUE INDEX developer_ppjbs_sales_case_active_unique ON developer_ppjbs (sales_case_id) WHERE status = 'ACTIVE'");
    }

    private function enforceLegacyDateRule(string $table, string $column): void
    {
        $name = "{$table}_{$column}_legacy_missing_check";

        if (DB::getDriverName() === 'pgsql') {
            DB::statement("ALTER TABLE {$table} ADD CONSTRAINT {$name} CHECK ({$column} IS NOT NULL OR (is_legacy_import = true AND legacy_date_missing = true))");

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

        DB::statement("DROP TRIGGER IF EXISTS {$name}_insert");
        DB::statement("DROP TRIGGER IF EXISTS {$name}_update");
    }
};
