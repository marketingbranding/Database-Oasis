<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('legacy_migration_provenances', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->foreignUlid('batch_id')->constrained('legacy_migration_batches')->cascadeOnDelete();
            $table->foreignUlid('candidate_id')->nullable()->constrained('legacy_migration_candidates')->nullOnDelete();
            $table->foreignUlid('orphan_id')->nullable()->constrained('legacy_migration_orphans')->nullOnDelete();
            $table->string('source_sheet');
            $table->integer('source_row')->nullable();
            $table->string('legacy_id')->nullable();
            $table->string('entity_type');
            $table->json('source_values');
            $table->string('source_fingerprint');
            $table->string('audit_fingerprint');
            $table->timestamps();

            $table->index('batch_id');
            $table->index('candidate_id');
            $table->index('orphan_id');
            $table->index('source_sheet');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('legacy_migration_provenances');
    }
};
