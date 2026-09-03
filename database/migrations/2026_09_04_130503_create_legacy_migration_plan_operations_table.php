<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('legacy_migration_plan_operations', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->foreignUlid('plan_id')->constrained('legacy_migration_plans')->cascadeOnDelete();
            $table->foreignUlid('candidate_id')->nullable()->constrained('legacy_migration_candidates')->nullOnDelete();
            $table->foreignUlid('orphan_id')->nullable()->constrained('legacy_migration_orphans')->nullOnDelete();
            $table->string('operation_type');
            $table->json('payload');
            $table->integer('sequence')->nullable();
            $table->text('error')->nullable();
            $table->timestamps();

            $table->index('plan_id');
            $table->index('candidate_id');
            $table->index('orphan_id');
            $table->index('operation_type');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('legacy_migration_plan_operations');
    }
};
