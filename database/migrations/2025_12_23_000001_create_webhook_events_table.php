<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     * 
     * PRIORITY-0: Webhook idempotency table
     * Prevents duplicate webhook processing
     */
    public function up(): void
    {
        Schema::create('webhook_events', function (Blueprint $table) {
            $table->id();
            $table->string('provider', 50); // stripe, paypal, paystack, etc.
            $table->string('event_id', 255)->unique(); // Provider's event ID (e.g., evt_xxx)
            $table->string('event_type', 100); // payment_intent.succeeded, refund.created, etc.
            $table->text('payload'); // Full webhook payload (JSON)
            $table->enum('status', ['pending', 'processing', 'processed', 'failed'])->default('pending');
            $table->text('processing_result')->nullable(); // Result of processing
            $table->text('error_message')->nullable(); // Error if processing failed
            $table->integer('retry_count')->default(0);
            $table->timestamp('processed_at')->nullable();
            $table->timestamps();

            // Indexes for fast lookups
            $table->index(['provider', 'event_id']);
            $table->index(['provider', 'event_type', 'status']);
            $table->index('created_at'); // For cleanup of old events
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('webhook_events');
    }
};
