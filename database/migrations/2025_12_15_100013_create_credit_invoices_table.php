<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateCreditInvoicesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('credit_invoices', function (Blueprint $table) {
            $table->id();

            // Relationships
            $table->foreignId('business_id')->constrained('users')->onDelete('cascade');

            // Invoice details
            $table->string('invoice_number')->unique();
            $table->date('invoice_date');
            $table->date('due_date');
            $table->date('period_start'); // Week start date
            $table->date('period_end'); // Week end date

            // Amounts
            $table->decimal('subtotal', 12, 2); // Total of all shift costs
            $table->decimal('late_fees', 10, 2)->default(0.00); // Any late fees
            $table->decimal('adjustments', 10, 2)->default(0.00); // Manual adjustments
            $table->decimal('total_amount', 12, 2); // Final amount due

            // Payment tracking
            $table->decimal('amount_paid', 12, 2)->default(0.00);
            $table->decimal('amount_due', 12, 2); // Remaining balance

            // Status
            $table->enum('status', [
                'draft',
                'issued',
                'sent',
                'partially_paid',
                'paid',
                'overdue',
                'cancelled'
            ])->default('draft')->index();

            // PDF generation
            $table->string('pdf_path')->nullable();
            $table->timestamp('pdf_generated_at')->nullable();

            // Timestamps
            $table->timestamp('sent_at')->nullable();
            $table->timestamp('paid_at')->nullable();
            $table->timestamps();
            $table->softDeletes();

            // Indexes
            $table->index('business_id');
            $table->index(['business_id', 'status']);
            $table->index('due_date');
            $table->index(['status', 'due_date']);
            $table->index(['period_start', 'period_end']);
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('credit_invoices');
    }
}
