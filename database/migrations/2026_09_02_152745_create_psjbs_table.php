<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('psjbs', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->foreignUlid('sales_case_id')->constrained()->restrictOnDelete();
            $table->date('psjb_date');
            $table->string('document_number')->nullable();
            $table->foreignUlid('coordinator_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('status')->default('ACTIVE');
            $table->text('notes')->nullable();
            $table->foreignUlid('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->softDeletes();

            $table->index('sales_case_id');
            $table->index('psjb_date');
            $table->index('document_number');
        });

        // Structural one-ACTIVE-PSJB-per-sales-case guard.
        // Partial unique index works identically on PostgreSQL and SQLite.
        DB::statement('CREATE UNIQUE INDEX psjbs_sales_case_active_unique ON psjbs (sales_case_id) WHERE status = \'ACTIVE\'');
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        DB::statement('DROP INDEX IF EXISTS psjbs_sales_case_active_unique');

        Schema::dropIfExists('psjbs');
    }
};
