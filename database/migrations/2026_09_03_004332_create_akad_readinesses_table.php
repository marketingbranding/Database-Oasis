<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('akad_readiness', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->foreignUlid('sales_case_id')->unique()->constrained()->restrictOnDelete();
            $table->unsignedTinyInteger('building_progress')->nullable();
            $table->string('building_status')->default('UNKNOWN');
            $table->string('dp_status')->default('UNKNOWN');
            $table->string('electricity_status')->default('UNKNOWN');
            $table->string('water_status')->default('UNKNOWN');
            $table->string('consumer_status')->default('UNKNOWN');
            $table->text('consumer_note')->nullable();
            $table->text('notes')->nullable();
            $table->foreignUlid('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index(['building_status', 'dp_status']);
            $table->index(['electricity_status', 'water_status']);
            $table->index('consumer_status');
        });

        if (DB::getDriverName() === 'pgsql') {
            DB::statement('ALTER TABLE akad_readiness ADD CONSTRAINT akad_readiness_building_progress_check CHECK (building_progress IS NULL OR building_progress BETWEEN 0 AND 100)');
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('akad_readiness');
    }
};
