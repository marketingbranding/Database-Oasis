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
        Schema::create('sales_cases', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->foreignUlid('consumer_id')->constrained()->restrictOnDelete();
            $table->foreignUlid('unit_id')->constrained()->restrictOnDelete();
            $table->foreignUlid('project_id')->constrained()->restrictOnDelete();
            $table->foreignUlid('branch_id')->constrained()->restrictOnDelete();
            $table->string('financing_type');
            $table->date('booking_date')->nullable();
            $table->string('source')->nullable();
            $table->string('current_stage')->default('DATA_KONSUMEN');
            $table->string('case_status')->default('ACTIVE');
            $table->ulid('previous_case_id')->nullable();
            $table->text('transfer_reason')->nullable();
            $table->foreignUlid('sales_pic_id')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignUlid('coordinator_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('closed_at')->nullable();
            $table->text('closed_reason')->nullable();
            $table->foreignUlid('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->softDeletes();

            $table->index('consumer_id');
            $table->index('unit_id');
            $table->index('branch_id');
            $table->index('project_id');
            $table->index('case_status');
            $table->index('current_stage');
        });

        // PostgreSQL adds the primary key after the foreign keys within Schema::create,
        // so a self-referencing foreign key must be added after the table exists.
        Schema::table('sales_cases', function (Blueprint $table) {
            $table->foreign('previous_case_id')
                ->references('id')
                ->on('sales_cases')
                ->nullOnDelete();
        });

        // Structural one-ACTIVE-case-per-unit and one-ACTIVE-case-per-consumer guards.
        // Partial unique indexes work identically on PostgreSQL and SQLite.
        DB::statement('CREATE UNIQUE INDEX sales_cases_unit_active_unique ON sales_cases (unit_id) WHERE case_status = \'ACTIVE\'');
        DB::statement('CREATE UNIQUE INDEX sales_cases_consumer_active_unique ON sales_cases (consumer_id) WHERE case_status = \'ACTIVE\'');
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        DB::statement('DROP INDEX IF EXISTS sales_cases_consumer_active_unique');
        DB::statement('DROP INDEX IF EXISTS sales_cases_unit_active_unique');

        Schema::table('sales_cases', function (Blueprint $table) {
            $table->dropForeign(['previous_case_id']);
        });

        Schema::dropIfExists('sales_cases');
    }
};
