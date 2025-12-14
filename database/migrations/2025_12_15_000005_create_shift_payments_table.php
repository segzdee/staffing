<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateShiftPaymentsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('shift_payments', function (Blueprint $table) {
            $table->id();

            // Relationships
            $table->foreignId('shift_assignment_id')->constrained('shift_assignments')->onDelete('cascade');
            $table->foreignId('worker_id')->constrained('users')->onDelete('cascade');
            $table->foreignId('business_id')->constrained('users')->onDelete('cascade');

            // Payment amounts
            $table->decimal('amount_gross', 10, 2); // total before fees
            $table->decimal('platform_fee', 10, 2); // OvertimeStaff commission
            $table->decimal('amount_net', 10, 2); // what worker receives

            // Payment lifecycle timestamps
            $table->timestamp('escrow_held_at')->nullable(); // when business payment captured
            $table->timestamp('released_at')->nullable(); // 15 min after shift completion
            $table->timestamp('payout_initiated_at')->nullable(); // when instant payout started
            $table->timestamp('payout_completed_at')->nullable(); // when worker received funds

            // Stripe integration
            $table->string('stripe_payment_intent_id')->nullable()->unique(); // business payment
            $table->string('stripe_transfer_id')->nullable()->unique(); // worker payout

            // Payment status
            $table->enum('status', [
                'pending_escrow',
                'in_escrow',
                'released',
                'paid_out',
                'failed',
                'disputed',
                'refunded'
            ])->default('pending_escrow')->index();

            // Dispute tracking
            $table->boolean('disputed')->default(false);
            $table->text('dispute_reason')->nullable();
            $table->timestamp('disputed_at')->nullable();
            $table->timestamp('resolved_at')->nullable();

            // Timestamps
            $table->timestamps();

            // Indexes for common queries
            $table->index('shift_assignment_id');
            $table->index('worker_id');
            $table->index('business_id');
            $table->index(['status', 'released_at']); // for 15-min payout job
            $table->index('created_at');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('shift_payments');
    }
}
