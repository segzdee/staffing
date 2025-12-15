<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * AGY-003: Add Stripe Connect fields to agency_profiles table
 *
 * This migration adds the necessary fields to support Stripe Connect
 * integration for agency payouts. Agencies need to onboard to Stripe
 * Connect to receive automated commission payouts.
 */
return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('agency_profiles', function (Blueprint $table) {
            // Stripe Connect Account Information
            $table->string('stripe_connect_account_id')->nullable()->after('paid_commission');
            $table->boolean('stripe_onboarding_complete')->default(false)->after('stripe_connect_account_id');
            $table->timestamp('stripe_onboarded_at')->nullable()->after('stripe_onboarding_complete');
            $table->boolean('stripe_payout_enabled')->default(false)->after('stripe_onboarded_at');

            // Additional Stripe Connect metadata
            $table->string('stripe_account_type')->nullable()->after('stripe_payout_enabled'); // express, standard, custom
            $table->boolean('stripe_charges_enabled')->default(false)->after('stripe_account_type');
            $table->boolean('stripe_details_submitted')->default(false)->after('stripe_charges_enabled');
            $table->json('stripe_requirements')->nullable()->after('stripe_details_submitted'); // Pending requirements from Stripe
            $table->string('stripe_default_currency', 3)->default('USD')->after('stripe_requirements');

            // Payout tracking
            $table->timestamp('last_payout_at')->nullable()->after('stripe_default_currency');
            $table->decimal('last_payout_amount', 12, 2)->nullable()->after('last_payout_at');
            $table->string('last_payout_status')->nullable()->after('last_payout_amount'); // pending, paid, failed
            $table->integer('total_payouts_count')->default(0)->after('last_payout_status');
            $table->decimal('total_payouts_amount', 14, 2)->default(0.00)->after('total_payouts_count');

            // Indexes for efficient querying
            $table->index('stripe_connect_account_id');
            $table->index('stripe_payout_enabled');
            $table->index('stripe_onboarding_complete');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('agency_profiles', function (Blueprint $table) {
            // Drop indexes first
            $table->dropIndex(['stripe_connect_account_id']);
            $table->dropIndex(['stripe_payout_enabled']);
            $table->dropIndex(['stripe_onboarding_complete']);

            // Drop columns
            $table->dropColumn([
                'stripe_connect_account_id',
                'stripe_onboarding_complete',
                'stripe_onboarded_at',
                'stripe_payout_enabled',
                'stripe_account_type',
                'stripe_charges_enabled',
                'stripe_details_submitted',
                'stripe_requirements',
                'stripe_default_currency',
                'last_payout_at',
                'last_payout_amount',
                'last_payout_status',
                'total_payouts_count',
                'total_payouts_amount',
            ]);
        });
    }
};
