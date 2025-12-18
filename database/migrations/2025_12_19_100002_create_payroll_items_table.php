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
        Schema::create('payroll_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('payroll_run_id')->constrained()->onDelete('cascade');
            $table->foreignId('user_id')->constrained();
            $table->foreignId('shift_id')->nullable()->constrained();
            $table->foreignId('shift_assignment_id')->nullable()->constrained('shift_assignments');
            $table->enum('type', ['regular', 'overtime', 'bonus', 'adjustment', 'reimbursement'])->default('regular');
            $table->text('description');
            $table->decimal('hours', 6, 2)->default(0);
            $table->decimal('rate', 10, 2)->default(0);
            $table->decimal('gross_amount', 10, 2);
            $table->decimal('deductions', 10, 2)->default(0);
            $table->decimal('tax_withheld', 10, 2)->default(0);
            $table->decimal('net_amount', 10, 2);
            $table->enum('status', ['pending', 'approved', 'paid', 'failed'])->default('pending');
            $table->string('payment_reference')->nullable();
            $table->string('stripe_transfer_id')->nullable();
            $table->timestamp('paid_at')->nullable();
            $table->timestamps();

            // Indexes for common queries
            $table->index(['payroll_run_id', 'user_id']);
            $table->index('status');
            $table->index('shift_id');
            $table->index('user_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('payroll_items');
    }
};
