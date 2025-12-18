<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * SL-004: Booking Confirmation System
     */
    public function up(): void
    {
        Schema::create('booking_confirmations', function (Blueprint $table) {
            $table->id();

            // Relationships
            $table->foreignId('shift_id')->constrained()->onDelete('cascade');
            $table->foreignId('worker_id')->constrained('users')->onDelete('cascade');
            $table->foreignId('business_id')->constrained('users')->onDelete('cascade');

            // Status tracking
            $table->enum('status', [
                'pending',
                'worker_confirmed',
                'business_confirmed',
                'fully_confirmed',
                'declined',
                'expired',
            ])->default('pending')->index();

            // Worker confirmation
            $table->boolean('worker_confirmed')->default(false);
            $table->timestamp('worker_confirmed_at')->nullable();

            // Business confirmation
            $table->boolean('business_confirmed')->default(false);
            $table->timestamp('business_confirmed_at')->nullable();

            // Confirmation code for QR / lookup
            $table->string('confirmation_code', 8)->unique();

            // Notes
            $table->text('worker_notes')->nullable();
            $table->text('business_notes')->nullable();

            // Decline tracking
            $table->foreignId('declined_by')->nullable()->constrained('users')->onDelete('set null');
            $table->timestamp('declined_at')->nullable();
            $table->text('decline_reason')->nullable();

            // Expiration
            $table->timestamp('expires_at');
            $table->timestamp('reminder_sent_at')->nullable();

            // Auto-confirmation for returning workers
            $table->boolean('auto_confirmed')->default(false);
            $table->text('auto_confirm_reason')->nullable();

            $table->timestamps();

            // Indexes
            $table->index(['shift_id', 'status']);
            $table->index(['worker_id', 'status']);
            $table->index(['business_id', 'status']);
            $table->index('expires_at');

            // Unique constraint: worker can only have one confirmation per shift
            $table->unique(['shift_id', 'worker_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('booking_confirmations');
    }
};
