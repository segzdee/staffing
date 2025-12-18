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
        Schema::create('payroll_runs', function (Blueprint $table) {
            $table->id();
            $table->string('reference')->unique(); // PR-2024-001
            $table->date('period_start');
            $table->date('period_end');
            $table->date('pay_date');
            $table->enum('status', ['draft', 'pending_approval', 'approved', 'processing', 'completed', 'failed'])->default('draft');
            $table->integer('total_workers')->default(0);
            $table->integer('total_shifts')->default(0);
            $table->decimal('gross_amount', 14, 2)->default(0);
            $table->decimal('total_deductions', 12, 2)->default(0);
            $table->decimal('total_taxes', 12, 2)->default(0);
            $table->decimal('net_amount', 14, 2)->default(0);
            $table->foreignId('created_by')->constrained('users');
            $table->foreignId('approved_by')->nullable()->constrained('users');
            $table->timestamp('approved_at')->nullable();
            $table->timestamp('processed_at')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();

            // Indexes for common queries
            $table->index('status');
            $table->index('period_start');
            $table->index('period_end');
            $table->index('pay_date');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('payroll_runs');
    }
};
