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
        Schema::create('bank_processes', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->foreignUlid('sales_case_id')->constrained()->restrictOnDelete();
            $table->foreignUlid('document_submission_id')->nullable()->constrained()->restrictOnDelete();
            $table->foreignUlid('bank_id')->nullable()->constrained()->restrictOnDelete();
            $table->string('response_type');
            $table->date('response_date');
            $table->string('sp3k_number')->nullable();
            $table->date('sp3k_date')->nullable();
            $table->unsignedBigInteger('credit_limit')->nullable();
            $table->unsignedInteger('tenor')->nullable();
            $table->boolean('is_authoritative')->default(false);
            $table->text('notes')->nullable();
            $table->foreignUlid('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->softDeletes();

            $table->index('sales_case_id');
            $table->index('document_submission_id');
            $table->index('bank_id');
            $table->index('response_type');
            $table->index('response_date');
            $table->index('sp3k_number');
        });

        DB::statement('CREATE UNIQUE INDEX bank_processes_authoritative_approval_unique ON bank_processes (sales_case_id) WHERE is_authoritative = true');
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        DB::statement('DROP INDEX IF EXISTS bank_processes_authoritative_approval_unique');

        Schema::dropIfExists('bank_processes');
    }
};
