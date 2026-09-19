<?php

use App\Support\Database\PartialUniqueGuard;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Allow legacy consumer imports without a valid NIK.
     *
     * Blank or malformed NIK values must never be fabricated, and their sales
     * cases must never be dropped. A nullable NIK stores them as NULL (the
     * unique index ignores NULL duplicates, so many legacy rows can coexist),
     * while the sales case carries the PERLU DICEK flag. Manual entry through
     * Filament still requires a 16-digit NIK.
     */
    public function up(): void
    {
        if (PartialUniqueGuard::isMysqlFamily()) {
            PartialUniqueGuard::modifyNullable('consumers', 'nik', 'VARCHAR(16)', true);

            return;
        }

        Schema::table('consumers', function (Blueprint $table) {
            $table->string('nik', 16)->nullable()->change();
        });
    }

    /**
     * Reverse the migrations.
     *
     * Destructive when NULL NIK rows exist; prefer a forward fix in production.
     */
    public function down(): void
    {
        if (PartialUniqueGuard::isMysqlFamily()) {
            PartialUniqueGuard::modifyNullable('consumers', 'nik', 'VARCHAR(16)', false);

            return;
        }

        Schema::table('consumers', function (Blueprint $table) {
            $table->string('nik', 16)->nullable(false)->change();
        });
    }
};
