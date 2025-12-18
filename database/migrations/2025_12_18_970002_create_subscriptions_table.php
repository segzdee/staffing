<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * FIN-011: Subscription Billing System
 *
 * Creates the subscriptions table to track user subscription status
 * and lifecycle. This is separate from Cashier's subscriptions table
 * to allow for more granular control and multi-plan support.
 */
return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('subscriptions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->foreignId('subscription_plan_id')->constrained()->onDelete('restrict');
            $table->string('stripe_subscription_id')->nullable()->unique();
            $table->string('stripe_customer_id')->nullable();
            $table->enum('status', [
                'active',
                'past_due',
                'canceled',
                'trialing',
                'paused',
                'incomplete',
                'incomplete_expired',
                'unpaid',
            ])->default('incomplete');
            $table->timestamp('trial_ends_at')->nullable();
            $table->timestamp('current_period_start')->nullable();
            $table->timestamp('current_period_end')->nullable();
            $table->timestamp('canceled_at')->nullable();
            $table->timestamp('paused_at')->nullable();
            $table->timestamp('ends_at')->nullable()->comment('When subscription actually ends if canceled');
            $table->boolean('cancel_at_period_end')->default(false);
            $table->string('cancellation_reason')->nullable();
            $table->json('metadata')->nullable()->comment('Additional subscription metadata');
            $table->timestamps();

            // Indexes
            $table->index(['user_id', 'status']);
            $table->index('stripe_subscription_id');
            $table->index('status');
            $table->index('current_period_end');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('subscriptions');
    }
};
