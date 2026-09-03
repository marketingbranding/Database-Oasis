<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('legacy_migration_orphan_resolutions', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->foreignUlid('orphan_id')->constrained('legacy_migration_orphans')->cascadeOnDelete();
            $table->string('decision');
            $table->string('resolution_type');
            $table->foreignUlid('target_candidate_id')->nullable()->constrained('legacy_migration_candidates')->nullOnDelete();
            $table->text('note');
            $table->foreignUlid('decided_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('decided_at');
            $table->string('source_fingerprint');
            $table->string('audit_fingerprint');
            $table->timestamps();

            $table->index('orphan_id');
            $table->index('decision');
            $table->index('resolution_type');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('legacy_migration_orphan_resolutions');
    }
};
