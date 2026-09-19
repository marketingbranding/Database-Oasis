<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('sales_cases', function (Blueprint $table) {
            $table->string('import_source')->nullable()->after('source');
            $table->string('import_source_id')->nullable()->after('import_source');
            $table->unique(['import_source', 'import_source_id']);
        });
    }

    public function down(): void
    {
        Schema::table('sales_cases', function (Blueprint $table) {
            $table->dropUnique(['import_source', 'import_source_id']);
            $table->dropColumn(['import_source_id', 'import_source']);
        });
    }
};
