<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('business_number_sequences', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->string('type', 20);
            $table->foreignUlid('branch_id')->constrained()->restrictOnDelete();
            $table->unsignedSmallInteger('year');
            $table->unsignedBigInteger('last_number')->default(0);
            $table->timestamps();
            $table->unique(['type', 'branch_id', 'year'], 'business_number_sequences_scope_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('business_number_sequences');
    }
};
