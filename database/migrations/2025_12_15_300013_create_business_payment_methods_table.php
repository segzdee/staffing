<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Migration: Business Payment Methods
 *
 * BIZ-REG-007: Business Payment Setup
 *
 * Supports multiple payment method types:
 * - Credit/Debit Card (Stripe)
 * - ACH / Bank Transfer (US)
 * - SEPA Direct Debit (EU)
 * - BACS (UK)
 */
return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('business_payment_methods', function (Blueprint $table) {
            $table->id();
            $table->foreignId('business_profile_id')->constrained()->onDelete('cascade');

            // Stripe identifiers
            $table->string('stripe_customer_id')->nullable()->index();
            $table->string('stripe_payment_method_id')->nullable()->index();
            $table->string('stripe_setup_intent_id')->nullable();

            // Payment method type: card, us_bank_account, sepa_debit, bacs_debit
            $table->string('type', 50)->default('card');

            // Display information (masked for security)
            $table->string('display_brand')->nullable(); // visa, mastercard, amex, etc.
            $table->string('display_last4', 4)->nullable(); // Last 4 digits
            $table->string('display_exp_month', 2)->nullable();
            $table->string('display_exp_year', 4)->nullable();

            // Bank account specific (ACH, SEPA, BACS)
            $table->string('bank_name')->nullable();
            $table->string('bank_account_type')->nullable(); // checking, savings
            $table->string('bank_routing_display')->nullable(); // Masked routing number
            $table->string('iban_last4', 4)->nullable(); // For SEPA
            $table->string('sort_code_display', 8)->nullable(); // For BACS

            // Verification status
            $table->string('verification_status')->default('pending'); // pending, verified, failed, requires_action
            $table->string('verification_method')->nullable(); // instant, micro_deposits, 3d_secure
            $table->timestamp('verification_requested_at')->nullable();
            $table->timestamp('verified_at')->nullable();
            $table->string('verification_failure_reason')->nullable();

            // Micro-deposit verification (ACH)
            $table->integer('micro_deposit_attempts')->default(0);
            $table->timestamp('micro_deposit_sent_at')->nullable();

            // 3D Secure status (cards)
            $table->boolean('three_d_secure_supported')->default(false);
            $table->string('three_d_secure_status')->nullable();

            // Billing address
            $table->string('billing_name')->nullable();
            $table->string('billing_email')->nullable();
            $table->string('billing_phone')->nullable();
            $table->string('billing_address_line1')->nullable();
            $table->string('billing_address_line2')->nullable();
            $table->string('billing_city')->nullable();
            $table->string('billing_state')->nullable();
            $table->string('billing_postal_code')->nullable();
            $table->string('billing_country', 2)->nullable(); // ISO country code

            // Default and status flags
            $table->boolean('is_default')->default(false);
            $table->boolean('is_active')->default(true);

            // Payment retry configuration
            $table->boolean('auto_retry_enabled')->default(true);
            $table->integer('max_retry_attempts')->default(3);
            $table->integer('failed_payment_count')->default(0);
            $table->timestamp('last_failed_at')->nullable();
            $table->string('last_failure_reason')->nullable();

            // Metadata
            $table->string('nickname')->nullable(); // User-defined name
            $table->json('metadata')->nullable();
            $table->string('currency', 3)->default('USD');

            $table->timestamps();
            $table->softDeletes();

            // Indexes
            $table->index(['business_profile_id', 'is_default']);
            $table->index(['business_profile_id', 'is_active']);
            $table->index(['type', 'verification_status']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('business_payment_methods');
    }
};
