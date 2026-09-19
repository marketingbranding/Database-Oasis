<?php

use App\Support\Database\PartialUniqueGuard;
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
        $partial = PartialUniqueGuard::supportsPartialIndexes();

        Schema::create('sales_cases', function (Blueprint $table) use ($partial) {
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

            if (! $partial) {
                $table->ulid('active_unit_key')->nullable();
            }
        });

        // PostgreSQL adds the primary key after the foreign keys within Schema::create,
        // so a self-referencing foreign key must be added after the table exists.
        Schema::table('sales_cases', function (Blueprint $table) {
            $table->foreign('previous_case_id')
                ->references('id')
                ->on('sales_cases')
                ->nullOnDelete();
        });

        PartialUniqueGuard::createPartialUnique('sales_cases', 'sales_cases_unit_active_unique', 'unit_id', "case_status = 'ACTIVE'");
        PartialUniqueGuard::createMysqlTriggerGuard(
            'sales_cases',
            'sales_cases_unit_active_unique',
            'active_unit_key',
            "CASE WHEN NEW.case_status = 'ACTIVE' THEN NEW.unit_id ELSE NULL END",
        );
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        PartialUniqueGuard::dropMysqlTriggerGuard('sales_cases', 'active_unit_key');
        PartialUniqueGuard::dropIndex('sales_cases_unit_active_unique', 'sales_cases');
        PartialUniqueGuard::dropIndex('sales_cases_consumer_active_unique', 'sales_cases');

        Schema::table('sales_cases', function (Blueprint $table) {
            $table->dropForeign(['previous_case_id']);
        });

        Schema::dropIfExists('sales_cases');
    }
};
