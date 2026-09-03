<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('legacy_migration_orphans', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->foreignUlid('batch_id')->constrained('legacy_migration_batches')->cascadeOnDelete();
            $table->foreignUlid('branch_id')->nullable()->constrained()->nullOnDelete();
            $table->string('source_sheet');
            $table->integer('source_row')->nullable();
            $table->string('orphan_code');
            $table->string('severity');
            $table->json('normalized_evidence');
            $table->json('candidate_matches');
            $table->string('status')->default('PENDING');
            $table->string('source_fingerprint');
            $table->string('audit_fingerprint');
            $table->timestamps();

            $table->index('batch_id');
            $table->index('source_sheet');
            $table->index('orphan_code');
            $table->index('status');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('legacy_migration_orphans');
    }
};
