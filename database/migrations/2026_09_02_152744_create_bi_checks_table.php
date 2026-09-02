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
        Schema::create('bi_checks', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->foreignUlid('sales_case_id')->constrained()->restrictOnDelete();
            $table->date('check_date');
            $table->string('result');
            $table->text('description')->nullable();
            $table->foreignUlid('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->softDeletes();

            $table->index('sales_case_id');
            $table->index('check_date');
            $table->index('result');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('bi_checks');
    }
};
