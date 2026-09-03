<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        foreach (['bi_checks', 'psjbs', 'document_submissions', 'bank_processes', 'developer_ppjbs'] as $tableName) {
            Schema::table($tableName, function (Blueprint $table) {
                $table->boolean('is_legacy_import')->default(false);
                $table->boolean('legacy_date_missing')->default(false);
            });
        }

        Schema::table('sales_cases', function (Blueprint $table) {
            $table->boolean('is_legacy_import')->default(false);
        });
    }

    public function down(): void
    {
        foreach (['bi_checks', 'psjbs', 'document_submissions', 'bank_processes', 'developer_ppjbs'] as $tableName) {
            Schema::table($tableName, function (Blueprint $table) {
                $table->dropColumn(['is_legacy_import', 'legacy_date_missing']);
            });
        }

        Schema::table('sales_cases', function (Blueprint $table) {
            $table->dropColumn('is_legacy_import');
        });
    }
};
