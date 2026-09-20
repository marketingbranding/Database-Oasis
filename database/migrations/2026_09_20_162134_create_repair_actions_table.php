<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('repair_actions', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->foreignUlid('branch_id')->constrained()->restrictOnDelete();
            $table->foreignUlid('sales_case_id')->constrained()->restrictOnDelete();
            $table->string('issue_code');
            $table->string('target_type');
            $table->ulid('target_id');
            $table->string('action_code');
            $table->string('repairability');
            $table->string('plan_fingerprint', 64);
            $table->json('before_payload');
            $table->json('after_payload');
            $table->json('evidence_payload');
            $table->foreignUlid('performed_by')->constrained('users')->restrictOnDelete();
            $table->timestamps();
            $table->index(['sales_case_id', 'issue_code']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('repair_actions');
    }
};
