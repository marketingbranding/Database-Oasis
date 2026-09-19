<?php

use App\Support\Database\PartialUniqueGuard;
use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    /**
     * Remove the one-ACTIVE-sales-case-per-consumer guard.
     *
     * A consumer may hold several ACTIVE sales cases (one id_transaksi_v2 =
     * one independent case); only the one-ACTIVE-case-per-unit guard remains.
     * Fresh installs never create the consumer index; this migration drops it
     * on databases migrated before the business-rule correction.
     */
    public function up(): void
    {
        PartialUniqueGuard::dropIndex('sales_cases_consumer_active_unique', 'sales_cases');
    }

    /**
     * Reverse the migrations.
     *
     * The consumer guard is intentionally not restored on MariaDB/MySQL.
     * Rolling back on PostgreSQL/SQLite recreates the legacy partial index.
     */
    public function down(): void
    {
        PartialUniqueGuard::createPartialUnique('sales_cases', 'sales_cases_consumer_active_unique', 'consumer_id', "case_status = 'ACTIVE'");
    }
};
