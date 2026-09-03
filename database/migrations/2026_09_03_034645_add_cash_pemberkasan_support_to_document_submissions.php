<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('document_submissions', function (Blueprint $table) {
            $table->string('type')->default('BANK')->after('status');
            $table->foreignUlid('bank_id')->nullable()->change();
        });

        if (DB::getDriverName() === 'pgsql') {
            DB::statement("ALTER TABLE document_submissions ADD CONSTRAINT document_submissions_bank_type_check CHECK ((type = 'BANK' AND bank_id IS NOT NULL) OR (type = 'CASH_INTERNAL' AND bank_id IS NULL))");
        }
    }

    public function down(): void
    {
        DB::table('document_submissions')->where('type', 'CASH_INTERNAL')->delete();

        if (DB::getDriverName() === 'pgsql') {
            DB::statement('ALTER TABLE document_submissions DROP CONSTRAINT IF EXISTS document_submissions_bank_type_check');
        }

        Schema::table('document_submissions', function (Blueprint $table) {
            $table->dropColumn('type');
            $table->foreignUlid('bank_id')->nullable(false)->change();
        });
    }
};
