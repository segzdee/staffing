<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateRefundsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('refunds', function (Blueprint $table) {
            $table->id();

            // Relationships
            $table->foreignId('business_id')->constrained('users')->onDelete('cascade');
            $table->foreignId('shift_id')->nullable()->constrained('shifts')->onDelete('set null');
            $table->foreignId('shift_payment_id')->nullable()->constrained('shift_payments')->onDelete('set null');
            $table->foreignId('processed_by_admin_id')->nullable()->constrained('users')->onDelete('set null');

            // Refund details
            $table->string('refund_number')->unique();
            $table->decimal('refund_amount', 10, 2);
            $table->decimal('original_amount', 10, 2); // Original transaction amount

            // Refund type and reason
            $table->enum('refund_type', [
                'auto_cancellation',     // Automatic refund for >72hr cancellations
                'dispute_resolution',    // Refund from dispute resolution
                'overcharge_correction', // Correction of billing error
                'penalty_waiver',        // Penalty refund
                'manual_adjustment',     // Admin-initiated refund
                'other'
            ])->index();

            $table->enum('refund_reason', [
                'cancellation_72hr',
                'business_cancellation',
                'worker_no_show',
                'shift_not_completed',
                'billing_error',
                'overcharge',
                'duplicate_charge',
                'dispute_resolved',
                'penalty_appeal_approved',
                'goodwill',
                'other'
            ])->index();

            $table->text('reason_description')->nullable();

            // Refund destination
            $table->enum('refund_method', [
                'original_payment_method', // Refund to card/bank
                'credit_balance',          // Add to business credit balance
                'manual',                  // Manual handling required
            ])->default('original_payment_method');

            // Status tracking
            $table->enum('status', [
                'pending',
                'processing',
                'completed',
                'failed',
                'cancelled'
            ])->default('pending')->index();

            // Payment gateway details
            $table->string('stripe_refund_id')->nullable()->index();
            $table->string('paypal_refund_id')->nullable();
            $table->string('payment_gateway')->nullable(); // stripe, paypal, etc.

            // Credit note details
            $table->string('credit_note_number')->nullable()->unique();
            $table->string('credit_note_pdf_path')->nullable();
            $table->timestamp('credit_note_generated_at')->nullable();

            // Processing details
            $table->text('failure_reason')->nullable();
            $table->json('metadata')->nullable(); // Additional data
            $table->text('admin_notes')->nullable();

            // Timestamps
            $table->timestamp('initiated_at')->nullable();
            $table->timestamp('processed_at')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->timestamp('failed_at')->nullable();
            $table->timestamps();
            $table->softDeletes();

            // Indexes for common queries
            $table->index('business_id');
            $table->index('shift_id');
            $table->index(['status', 'created_at']);
            $table->index(['refund_type', 'status']);
            $table->index('initiated_at');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('refunds');
    }
}
