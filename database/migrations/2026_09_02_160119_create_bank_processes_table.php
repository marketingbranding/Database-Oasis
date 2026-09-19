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

        Schema::create('bank_processes', function (Blueprint $table) use ($partial) {
            $table->ulid('id')->primary();
            $table->foreignUlid('sales_case_id')->constrained()->restrictOnDelete();
            $table->foreignUlid('document_submission_id')->nullable()->constrained()->restrictOnDelete();
            $table->foreignUlid('bank_id')->nullable()->constrained()->restrictOnDelete();
            $table->string('response_type');
            $table->date('response_date');
            $table->string('sp3k_number')->nullable();
            $table->date('sp3k_date')->nullable();
            $table->unsignedBigInteger('credit_limit')->nullable();
            $table->unsignedInteger('tenor')->nullable();
            $table->boolean('is_authoritative')->default(false);
            $table->text('notes')->nullable();
            $table->foreignUlid('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->softDeletes();

            $table->index('sales_case_id');
            $table->index('document_submission_id');
            $table->index('bank_id');
            $table->index('response_type');
            $table->index('response_date');
            $table->index('sp3k_number');

            if (! $partial) {
                // MariaDB/MySQL: reproduce the partial unique guard. The
                // truthy check compiles on every driver, so only rows with an
                // authoritative approval participate in the unique index.
                $table->ulid('authoritative_case_key')->nullable()
                    ->storedAs('case when is_authoritative then sales_case_id else null end');
                $table->unique('authoritative_case_key', 'bank_processes_authoritative_approval_unique');
            }
        });

        // Structural one-authoritative-bank-process-per-sales-case guard.
        PartialUniqueGuard::createPartialUnique('bank_processes', 'bank_processes_authoritative_approval_unique', 'sales_case_id', 'is_authoritative = true');
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        PartialUniqueGuard::dropIndex('bank_processes_authoritative_approval_unique', 'bank_processes');

        Schema::dropIfExists('bank_processes');
    }
};
