<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('akad_targets', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->foreignUlid('branch_id')->constrained()->restrictOnDelete();
            $table->foreignUlid('project_id')->nullable()->constrained()->restrictOnDelete();
            $table->date('period_month');
            $table->unsignedInteger('target');
            $table->foreignUlid('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignUlid('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index(['period_month', 'branch_id']);
            $table->unique(['project_id', 'period_month']);
        });

        DB::statement('CREATE UNIQUE INDEX akad_targets_branch_month_unique ON akad_targets (branch_id, period_month) WHERE project_id IS NULL');

        if (DB::getDriverName() === 'pgsql') {
            DB::statement('ALTER TABLE akad_targets ADD CONSTRAINT akad_targets_period_first_day_check CHECK (EXTRACT(DAY FROM period_month) = 1)');
            DB::statement('ALTER TABLE akad_targets ADD CONSTRAINT akad_targets_nonnegative_check CHECK (target >= 0)');
        }
    }

    public function down(): void
    {
        DB::statement('DROP INDEX IF EXISTS akad_targets_branch_month_unique');
        Schema::dropIfExists('akad_targets');
    }
};
