<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('document_submissions', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->foreignUlid('sales_case_id')->constrained()->restrictOnDelete();
            $table->foreignUlid('psjb_id')->constrained()->restrictOnDelete();
            $table->foreignUlid('bank_id')->constrained()->restrictOnDelete();
            $table->date('submission_date');
            $table->string('bank_branch')->nullable();
            $table->unsignedInteger('sequence');
            $table->string('status')->default('SUBMITTED');
            $table->text('notes')->nullable();
            $table->foreignUlid('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->softDeletes();

            $table->index('sales_case_id');
            $table->index('psjb_id');
            $table->index('bank_id');
            $table->index('submission_date');
            $table->index('status');
            $table->unique(['sales_case_id', 'sequence']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('document_submissions');
    }
};
