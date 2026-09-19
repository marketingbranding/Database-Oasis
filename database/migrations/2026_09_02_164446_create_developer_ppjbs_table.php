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

        Schema::create('developer_ppjbs', function (Blueprint $table) use ($partial) {
            $table->ulid('id')->primary();
            $table->foreignUlid('sales_case_id')->constrained()->restrictOnDelete();
            $table->foreignUlid('bank_process_id')->nullable()->constrained()->restrictOnDelete();
            $table->string('document_number')->nullable();
            $table->date('document_date');
            $table->string('status')->default('ACTIVE');
            $table->text('notes')->nullable();
            $table->foreignUlid('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->softDeletes();

            $table->index('sales_case_id');
            $table->index('bank_process_id');
            $table->index('document_number');
            $table->index('document_date');
            $table->index('status');

            if (! $partial) {
                // MariaDB/MySQL: reproduce the partial unique guard with a
                // nullable generated column so history rows stay unrestricted.
                $table->ulid('active_ppjb_key')->nullable()
                    ->storedAs("case when status = 'ACTIVE' then sales_case_id else null end");
                $table->unique('active_ppjb_key', 'developer_ppjbs_sales_case_active_unique');
            }
        });

        // Structural one-ACTIVE-developer-PPJB-per-sales-case guard.
        PartialUniqueGuard::createPartialUnique('developer_ppjbs', 'developer_ppjbs_sales_case_active_unique', 'sales_case_id', "status = 'ACTIVE'");
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        PartialUniqueGuard::dropIndex('developer_ppjbs_sales_case_active_unique', 'developer_ppjbs');

        Schema::dropIfExists('developer_ppjbs');
    }
};
