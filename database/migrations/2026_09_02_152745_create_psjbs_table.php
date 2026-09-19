<?php

use App\Support\Database\PartialUniqueGuard;
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
        $partial = PartialUniqueGuard::supportsPartialIndexes();

        Schema::create('psjbs', function (Blueprint $table) use ($partial) {
            $table->ulid('id')->primary();
            $table->foreignUlid('sales_case_id')->constrained()->restrictOnDelete();
            $table->date('psjb_date');
            $table->string('document_number')->nullable();
            $table->foreignUlid('coordinator_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('status')->default('ACTIVE');
            $table->text('notes')->nullable();
            $table->foreignUlid('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->softDeletes();

            $table->index('sales_case_id');
            $table->index('psjb_date');
            $table->index('document_number');

            if (! $partial) {
                // MariaDB/MySQL: reproduce the partial unique guard with a
                // nullable generated column (NULL rows are ignored by the
                // unique index, so history rows stay unrestricted).
                $table->ulid('active_psjb_key')->nullable()
                    ->storedAs("case when status = 'ACTIVE' then sales_case_id else null end");
                $table->unique('active_psjb_key', 'psjbs_sales_case_active_unique');
            }
        });

        // Structural one-ACTIVE-PSJB-per-sales-case guard.
        PartialUniqueGuard::createPartialUnique('psjbs', 'psjbs_sales_case_active_unique', 'sales_case_id', "status = 'ACTIVE'");
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        PartialUniqueGuard::dropIndex('psjbs_sales_case_active_unique', 'psjbs');

        Schema::dropIfExists('psjbs');
    }
};
