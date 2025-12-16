<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * STAFF-REG-008: Worker Payment Setup Fields
 *
 * Adds Stripe Connect fields to worker_profiles for payout management.
 */
return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('worker_profiles', function (Blueprint $table) {
            // Stripe Connect Account
            $table->string('stripe_connect_account_id')->nullable()->after('linkedin_url');
            $table->string('stripe_account_type')->nullable()->after('stripe_connect_account_id'); // express, standard, custom
            $table->boolean('stripe_onboarding_complete')->default(false)->after('stripe_account_type');
            $table->timestamp('stripe_onboarding_completed_at')->nullable()->after('stripe_onboarding_complete');

            // Account status
            $table->boolean('stripe_charges_enabled')->default(false)->after('stripe_onboarding_completed_at');
            $table->boolean('stripe_payouts_enabled')->default(false)->after('stripe_charges_enabled');
            $table->boolean('stripe_details_submitted')->default(false)->after('stripe_payouts_enabled');

            // Account requirements
            $table->json('stripe_requirements_current')->nullable()->after('stripe_details_submitted');
            $table->json('stripe_requirements_eventually_due')->nullable()->after('stripe_requirements_current');
            $table->string('stripe_disabled_reason')->nullable()->after('stripe_requirements_eventually_due');

            // Payout schedule
            $table->string('payout_schedule')->default('daily')->after('stripe_disabled_reason'); // daily, weekly, monthly
            $table->string('payout_day')->nullable()->after('payout_schedule'); // For weekly/monthly: day name or number
            $table->string('preferred_payout_method')->nullable()->after('payout_day'); // bank_account, debit_card

            // Payout tracking
            $table->timestamp('last_payout_at')->nullable()->after('preferred_payout_method');
            $table->decimal('last_payout_amount', 10, 2)->nullable()->after('last_payout_at');
            $table->integer('total_payouts')->default(0)->after('last_payout_amount');
            $table->decimal('lifetime_payout_amount', 12, 2)->default(0)->after('total_payouts');

            // Instant payouts
            $table->boolean('instant_payouts_enabled')->default(false)->after('lifetime_payout_amount');
            $table->decimal('instant_payout_fee_percentage', 5, 2)->default(1.5)->after('instant_payouts_enabled');

            // Tax information
            $table->boolean('tax_info_collected')->default(false)->after('instant_payout_fee_percentage');
            $table->string('tax_form_type')->nullable()->after('tax_info_collected'); // W9, W8BEN, etc.
            $table->timestamp('tax_info_submitted_at')->nullable()->after('tax_form_type');

            // Indexes
            $table->index('stripe_connect_account_id');
            $table->index('stripe_onboarding_complete');
            $table->index('stripe_payouts_enabled');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('worker_profiles', function (Blueprint $table) {
            $table->dropIndex(['stripe_connect_account_id']);
            $table->dropIndex(['stripe_onboarding_complete']);
            $table->dropIndex(['stripe_payouts_enabled']);

            $table->dropColumn([
                'stripe_connect_account_id',
                'stripe_account_type',
                'stripe_onboarding_complete',
                'stripe_onboarding_completed_at',
                'stripe_charges_enabled',
                'stripe_payouts_enabled',
                'stripe_details_submitted',
                'stripe_requirements_current',
                'stripe_requirements_eventually_due',
                'stripe_disabled_reason',
                'payout_schedule',
                'payout_day',
                'preferred_payout_method',
                'last_payout_at',
                'last_payout_amount',
                'total_payouts',
                'lifetime_payout_amount',
                'instant_payouts_enabled',
                'instant_payout_fee_percentage',
                'tax_info_collected',
                'tax_form_type',
                'tax_info_submitted_at',
            ]);
        });
    }
};
