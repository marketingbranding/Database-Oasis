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
            PartialUniqueGuard::modifyNullable('document_submissions', 'psjb_id', 'CHAR(26)', true);

            return;
        }

        Schema::table('document_submissions', function (Blueprint $table) {
            $table->foreignUlid('psjb_id')->nullable()->change();
        });
    }

    public function down(): void
    {
        if (PartialUniqueGuard::isMysqlFamily()) {
            PartialUniqueGuard::modifyNullable('document_submissions', 'psjb_id', 'CHAR(26)', false);

            return;
        }

        Schema::table('document_submissions', function (Blueprint $table) {
            $table->foreignUlid('psjb_id')->nullable(false)->change();
        });
    }
};
