<?php

use App\Support\Database\PartialUniqueGuard;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        $partial = PartialUniqueGuard::supportsPartialIndexes();

        Schema::create('akad_targets', function (Blueprint $table) use ($partial) {
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

            if (! $partial) {
                // MariaDB/MySQL: reproduce "unique branch+month when no
                // project" with a nullable generated column. Project-scoped
                // rows store NULL and never collide with each other here.
                $table->ulid('global_branch_key')->nullable()
                    ->storedAs('case when project_id is null then branch_id else null end');
                $table->unique(['global_branch_key', 'period_month'], 'akad_targets_branch_month_unique');
            }
        });

        PartialUniqueGuard::createPartialUnique('akad_targets', 'akad_targets_branch_month_unique', 'branch_id, period_month', 'project_id IS NULL');

        if (DB::getDriverName() === 'pgsql') {
            DB::statement('ALTER TABLE akad_targets ADD CONSTRAINT akad_targets_period_first_day_check CHECK (EXTRACT(DAY FROM period_month) = 1)');
            DB::statement('ALTER TABLE akad_targets ADD CONSTRAINT akad_targets_nonnegative_check CHECK (target >= 0)');
        }
    }

    public function down(): void
    {
        PartialUniqueGuard::dropIndex('akad_targets_branch_month_unique', 'akad_targets');
        Schema::dropIfExists('akad_targets');
    }
};
