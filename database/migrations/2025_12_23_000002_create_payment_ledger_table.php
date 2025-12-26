<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     * 
     * PRIORITY-0: Payment ledger (single source of truth)
     * Immutable ledger entries for all payment mutations
     */
    public function up(): void
    {
        Schema::create('payment_ledger', function (Blueprint $table) {
            $table->id();
            $table->foreignId('shift_payment_id')->nullable()->constrained('shift_payments')->onDelete('cascade');
            $table->foreignId('shift_assignment_id')->nullable()->constrained('shift_assignments')->onDelete('cascade');
            $table->foreignId('user_id')->constrained('users')->onDelete('cascade');
            
            // Payment provider tracking
            $table->string('provider', 50); // stripe, paypal, etc.
            $table->string('provider_payment_id', 255)->nullable(); // pi_xxx, pay_xxx, etc.
            $table->string('provider_transfer_id', 255)->nullable(); // Transfer/payout ID
            
            // Ledger entry details
            $table->enum('entry_type', [
                'escrow_captured',
                'escrow_released',
                'refund_initiated',
                'refund_completed',
                'dispute_opened',
                'dispute_resolved',
                'payout_initiated',
                'payout_succeeded',
                'payout_failed',
                'fee_deducted',
                'commission_deducted',
            ]);
            
            // Amounts (stored in cents/smallest currency unit)
            $table->bigInteger('amount'); // Entry amount (positive or negative)
            $table->bigInteger('balance_after'); // Balance after this entry
            $table->string('currency', 3)->default('USD');
            
            // Metadata
            $table->json('metadata')->nullable(); // Additional entry data
            $table->string('reference', 255)->nullable(); // Reference number
            $table->text('description')->nullable();
            
            // Audit
            $table->foreignId('created_by')->nullable()->constrained('users')->onDelete('set null');
            $table->string('webhook_event_id', 255)->nullable(); // Link to webhook_events if from webhook
            $table->timestamps();

            // Indexes
            $table->index('shift_payment_id');
            $table->index('shift_assignment_id');
            $table->index('user_id');
            $table->index(['provider', 'provider_payment_id']);
            $table->index('entry_type');
            $table->index('created_at');
            
            // Unique constraint: one escrow record per payment_intent_id
            $table->unique(['provider', 'provider_payment_id', 'entry_type'], 'unique_provider_payment_entry');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('payment_ledger');
    }
};
