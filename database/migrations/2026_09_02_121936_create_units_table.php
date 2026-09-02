<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('units', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->foreignUlid('project_id')->constrained()->restrictOnDelete();
            $table->string('unit_code');
            $table->string('block')->nullable();
            $table->string('number')->nullable();
            $table->string('status')->default('TERSEDIA');
            $table->unsignedTinyInteger('building_progress')->nullable();
            $table->string('electricity_status')->nullable();
            $table->string('water_status')->nullable();
            $table->timestamps();

            $table->unique(['project_id', 'unit_code']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('units');
    }
};
