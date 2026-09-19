<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Lightweight PERLU DICEK / NEEDS REVIEW flag for sales cases.
     *
     * Data-quality problems (missing/invalid NIK, ambiguous unit, conflicting
     * profile values, milestone inconsistency, legacy anomalies) must not
     * block a sales case from existing. The flag keeps the case usable while
     * making the problem visible, replacing the need for a heavy
     * reconciliation gate during migration.
     */
    public function up(): void
    {
        Schema::table('sales_cases', function (Blueprint $table) {
            $table->boolean('needs_review')->default(false)->after('transfer_reason');
            $table->text('needs_review_reason')->nullable()->after('needs_review');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('sales_cases', function (Blueprint $table) {
            $table->dropColumn(['needs_review_reason', 'needs_review']);
        });
    }
};
