<?php

use App\Support\Database\PartialUniqueGuard;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (PartialUniqueGuard::isMysqlFamily()) {
            PartialUniqueGuard::modifyNullable('sales_cases', 'unit_id', 'CHAR(26)', true);

            return;
        }

        PartialUniqueGuard::dropIndex('sales_cases_unit_active_unique', 'sales_cases');

        Schema::table('sales_cases', function (Blueprint $table) {
            $table->foreignUlid('unit_id')->nullable()->change();
        });

        PartialUniqueGuard::createPartialUnique('sales_cases', 'sales_cases_unit_active_unique', 'unit_id', "case_status = 'ACTIVE'");
    }

    public function down(): void
    {
        if (PartialUniqueGuard::isMysqlFamily()) {
            PartialUniqueGuard::modifyNullable('sales_cases', 'unit_id', 'CHAR(26)', false);

            return;
        }

        PartialUniqueGuard::dropIndex('sales_cases_unit_active_unique', 'sales_cases');

        Schema::table('sales_cases', function (Blueprint $table) {
            $table->foreignUlid('unit_id')->nullable(false)->change();
        });

        PartialUniqueGuard::createPartialUnique('sales_cases', 'sales_cases_unit_active_unique', 'unit_id', "case_status = 'ACTIVE'");
    }
};
