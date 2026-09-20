<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('bank_processes', function (Blueprint $table) {
            $table->string('sp3k_code')->nullable()->unique()->after('sp3k_number');
        });
        Schema::table('developer_ppjbs', function (Blueprint $table) {
            $table->string('ppjb_code')->nullable()->unique()->after('document_number');
        });
    }

    public function down(): void
    {
        Schema::table('bank_processes', function (Blueprint $table) {
            $table->dropUnique(['sp3k_code']);
            $table->dropColumn('sp3k_code');
        });
        Schema::table('developer_ppjbs', function (Blueprint $table) {
            $table->dropUnique(['ppjb_code']);
            $table->dropColumn('ppjb_code');
        });
    }
};
