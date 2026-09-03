<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('legacy_migration_candidate_exceptions', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->foreignUlid('candidate_id')->constrained('legacy_migration_candidates')->cascadeOnDelete();
            $table->string('code');
            $table->string('severity');
            $table->string('source_sheet');
            $table->integer('source_row')->nullable();
            $table->string('entity_type');
            $table->text('message');
            $table->json('evidence');
            $table->timestamps();

            $table->index('candidate_id');
            $table->index('severity');
            $table->index('code');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('legacy_migration_candidate_exceptions');
    }
};
