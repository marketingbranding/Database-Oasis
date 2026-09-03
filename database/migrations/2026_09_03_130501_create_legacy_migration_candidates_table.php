<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('legacy_migration_candidates', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->foreignUlid('batch_id')->constrained('legacy_migration_batches')->cascadeOnDelete();
            $table->foreignUlid('branch_id')->nullable()->constrained()->nullOnDelete();
            $table->string('source_candidate_key');
            $table->json('proposed_consumer');
            $table->json('proposed_unit');
            $table->json('proposed_sales_case');
            $table->json('proposed_history');
            $table->string('confidence');
            $table->string('readiness');
            $table->string('lifecycle');
            $table->string('financing_type')->nullable();
            $table->json('source_evidence');
            $table->string('source_fingerprint');
            $table->timestamps();

            $table->unique(['batch_id', 'source_candidate_key']);
            $table->index('readiness');
            $table->index('confidence');
            $table->index('lifecycle');
            $table->index('financing_type');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('legacy_migration_candidates');
    }
};
