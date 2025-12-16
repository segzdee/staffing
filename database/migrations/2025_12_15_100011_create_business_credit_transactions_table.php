<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateBusinessCreditTransactionsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('business_credit_transactions', function (Blueprint $table) {
            $table->id();

            // Relationships
            $table->foreignId('business_id')->constrained('users')->onDelete('cascade');
            $table->foreignId('shift_id')->nullable()->constrained('shifts')->onDelete('set null');
            $table->unsignedBigInteger('invoice_id')->nullable(); // FK will be added after credit_invoices table is created

            // Transaction details
            $table->enum('transaction_type', [
                'charge',           // Shift cost charged to credit
                'payment',          // Payment received
                'late_fee',         // Late payment fee
                'refund',           // Refund issued
                'adjustment',       // Manual adjustment
                'credit_increase',  // Credit limit increase
                'credit_decrease'   // Credit limit decrease
            ])->index();

            $table->decimal('amount', 12, 2); // Positive for charges/fees, negative for payments/refunds
            $table->decimal('balance_before', 12, 2);
            $table->decimal('balance_after', 12, 2);

            // Description and metadata
            $table->string('description');
            $table->text('notes')->nullable();
            $table->json('metadata')->nullable(); // Additional data like shift details

            // References
            $table->string('reference_id')->nullable()->index(); // External payment reference
            $table->string('reference_type')->nullable(); // stripe_payment, bank_transfer, etc.

            // Timestamps
            $table->timestamps();

            // Indexes
            $table->index('business_id');
            $table->index(['business_id', 'transaction_type']);
            $table->index(['business_id', 'created_at']);
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('business_credit_transactions');
    }
}
