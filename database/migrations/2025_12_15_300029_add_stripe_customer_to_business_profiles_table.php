<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Migration: Add Stripe Customer fields to Business Profiles
 *
 * BIZ-REG-007: Business Payment Setup
 *
 * Adds Stripe Customer ID and payment configuration fields
 * to the business_profiles table.
 */
return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('business_profiles', function (Blueprint $table) {
            // Stripe Customer ID (main billing entity)
            $table->string('stripe_customer_id')->nullable()->after('user_id');

            // Payment setup status
            $table->boolean('payment_setup_complete')->default(false)->after('stripe_customer_id');
            $table->timestamp('payment_setup_at')->nullable()->after('payment_setup_complete');

            // Default payment method reference
            $table->foreignId('default_payment_method')->nullable()->after('payment_setup_at');

            // Payment retry configuration
            $table->integer('payment_retry_max_attempts')->default(3);
            $table->integer('payment_retry_interval_days')->default(3);
            $table->boolean('payment_auto_retry')->default(true);

            // Billing configuration
            $table->string('billing_email')->nullable();
            $table->boolean('send_payment_receipts')->default(true);
            $table->boolean('invoice_auto_pay')->default(true);

            // First shift wizard status
            $table->boolean('first_shift_posted')->default(false);
            $table->timestamp('first_shift_posted_at')->nullable();

            // Promotional credits
            $table->integer('promotional_credits_cents')->default(0);
            $table->timestamp('credits_expire_at')->nullable();

            // Index
            $table->index('stripe_customer_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('business_profiles', function (Blueprint $table) {
            $table->dropIndex(['stripe_customer_id']);

            $table->dropColumn([
                'stripe_customer_id',
                'payment_setup_complete',
                'payment_setup_at',
                'default_payment_method',
                'payment_retry_max_attempts',
                'payment_retry_interval_days',
                'payment_auto_retry',
                'billing_email',
                'send_payment_receipts',
                'invoice_auto_pay',
                'first_shift_posted',
                'first_shift_posted_at',
                'promotional_credits_cents',
                'credits_expire_at',
            ]);
        });
    }
};
