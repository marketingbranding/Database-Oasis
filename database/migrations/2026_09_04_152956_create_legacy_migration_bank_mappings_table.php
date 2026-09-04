<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('legacy_migration_bank_mappings', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->foreignUlid('batch_id')->constrained('legacy_migration_batches')->cascadeOnDelete();
            $table->string('raw_legacy_value');
            $table->string('normalized_alias');
            $table->foreignUlid('target_bank_id')->constrained('banks')->restrictOnDelete();
            $table->foreignUlid('approved_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('approved_at');
            $table->text('reason');
            $table->string('source_fingerprint');
            $table->string('audit_fingerprint');
            $table->timestamps();

            $table->unique(['batch_id', 'normalized_alias']);
            $table->index('target_bank_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('legacy_migration_bank_mappings');
    }
};
