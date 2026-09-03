<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('legacy_migration_batches', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->foreignUlid('branch_id')->nullable()->constrained()->nullOnDelete();
            $table->string('source_filename');
            $table->string('source_fingerprint');
            $table->string('audit_fingerprint');
            $table->json('source_row_counts');
            $table->string('status')->default('AUDITED');
            $table->foreignUlid('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->timestamp('completed_at')->nullable();

            $table->index('source_fingerprint');
            $table->index('status');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('legacy_migration_batches');
    }
};
