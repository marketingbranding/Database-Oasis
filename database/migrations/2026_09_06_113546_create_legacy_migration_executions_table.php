<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('legacy_migration_executions', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->foreignUlid('plan_id')->constrained('legacy_migration_plans')->restrictOnDelete();
            $table->string('plan_fingerprint', 64);
            $table->string('status', 20);
            $table->foreignUlid('started_by')->nullable()->constrained('users')->nullOnDelete();
            $table->string('environment');
            $table->string('database_connection');
            $table->string('database_name');
            $table->text('backup_reference')->nullable();
            $table->timestamp('backup_created_at')->nullable();
            $table->timestamp('started_at')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->json('preflight_summary')->nullable();
            $table->json('result_summary')->nullable();
            $table->text('failure_reason')->nullable();
            $table->timestamps();

            $table->unique(['plan_id', 'plan_fingerprint']);
            $table->index('status');
        });

        Schema::table('legacy_migration_provenances', function (Blueprint $table) {
            $table->foreignUlid('execution_id')->nullable()->after('id')->constrained('legacy_migration_executions')->restrictOnDelete();
            $table->foreignUlid('plan_id')->nullable()->after('execution_id')->constrained('legacy_migration_plans')->restrictOnDelete();
            $table->foreignUlid('operation_id')->nullable()->after('plan_id')->constrained('legacy_migration_plan_operations')->restrictOnDelete();
            $table->string('target_type')->nullable()->after('entity_type');
            $table->ulid('target_id')->nullable()->after('target_type');
            $table->index(['execution_id', 'target_type', 'target_id']);
        });
    }

    public function down(): void
    {
        Schema::table('legacy_migration_provenances', function (Blueprint $table) {
            $table->dropConstrainedForeignId('execution_id');
            $table->dropConstrainedForeignId('plan_id');
            $table->dropConstrainedForeignId('operation_id');
            $table->dropColumn(['target_type', 'target_id']);
        });

        Schema::dropIfExists('legacy_migration_executions');
    }
};
