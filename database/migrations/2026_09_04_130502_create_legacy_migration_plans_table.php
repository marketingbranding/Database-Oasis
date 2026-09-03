<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('legacy_migration_plans', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->foreignUlid('batch_id')->constrained('legacy_migration_batches')->cascadeOnDelete();
            $table->string('status')->default('GENERATED');
            $table->string('source_fingerprint');
            $table->string('audit_fingerprint');
            $table->string('candidate_state_fingerprint');
            $table->string('review_resolution_fingerprint');
            $table->string('plan_fingerprint')->unique();
            $table->json('summary_totals');
            $table->json('simulation_result')->nullable();
            $table->foreignUlid('generated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('generated_at');
            $table->timestamps();

            $table->index('batch_id');
            $table->index('status');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('legacy_migration_plans');
    }
};
