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
        Schema::create('developer_ppjbs', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->foreignUlid('sales_case_id')->constrained()->restrictOnDelete();
            $table->foreignUlid('bank_process_id')->nullable()->constrained()->restrictOnDelete();
            $table->string('document_number')->nullable();
            $table->date('document_date');
            $table->string('status')->default('ACTIVE');
            $table->text('notes')->nullable();
            $table->foreignUlid('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->softDeletes();

            $table->index('sales_case_id');
            $table->index('bank_process_id');
            $table->index('document_number');
            $table->index('document_date');
            $table->index('status');
        });

        DB::statement('CREATE UNIQUE INDEX developer_ppjbs_sales_case_active_unique ON developer_ppjbs (sales_case_id) WHERE status = \'ACTIVE\'');
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        DB::statement('DROP INDEX IF EXISTS developer_ppjbs_sales_case_active_unique');

        Schema::dropIfExists('developer_ppjbs');
    }
};
