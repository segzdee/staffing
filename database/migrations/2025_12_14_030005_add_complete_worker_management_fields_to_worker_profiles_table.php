<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddCompleteWorkerManagementFieldsToWorkerProfilesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('worker_profiles', function (Blueprint $table) {
            // WKR-001: Onboarding tracking
            $table->boolean('onboarding_completed')->default(false)->after('user_id');
            $table->integer('onboarding_step')->nullable()->after('onboarding_completed');
            $table->timestamp('onboarding_completed_at')->nullable()->after('onboarding_step');
            $table->boolean('identity_verified')->default(false)->after('onboarding_completed_at');
            $table->timestamp('identity_verified_at')->nullable()->after('identity_verified');
            $table->string('identity_verification_method')->nullable()->after('identity_verified_at');

            // WKR-004: Tier system
            $table->enum('subscription_tier', ['bronze', 'silver', 'gold', 'platinum'])->default('bronze')->after('identity_verification_method');
            $table->timestamp('tier_expires_at')->nullable()->after('subscription_tier');
            $table->timestamp('tier_upgraded_at')->nullable()->after('tier_expires_at');

            // WKR-005: Enhanced reliability metrics
            $table->integer('total_late_arrivals')->default(0)->after('total_cancellations');
            $table->integer('total_early_departures')->default(0)->after('total_late_arrivals');
            $table->integer('total_no_acknowledgments')->default(0)->after('total_early_departures');
            $table->integer('average_response_time_minutes')->default(0)->after('total_no_acknowledgments');

            // WKR-009: Earnings tracking
            $table->decimal('total_earnings', 10, 2)->default(0)->after('average_response_time_minutes');
            $table->decimal('pending_earnings', 10, 2)->default(0)->after('total_earnings');
            $table->decimal('withdrawn_earnings', 10, 2)->default(0)->after('pending_earnings');
            $table->decimal('average_hourly_earned', 8, 2)->nullable()->after('withdrawn_earnings');

            // WKR-010: Referral tracking
            $table->string('referral_code', 20)->unique()->nullable()->after('average_hourly_earned');
            $table->string('referred_by', 20)->nullable()->after('referral_code');
            $table->integer('total_referrals')->default(0)->after('referred_by');
            $table->decimal('referral_earnings', 10, 2)->default(0)->after('total_referrals');

            // Additional profile fields (excluding columns that already exist)
            // location_lat, location_lng, emergency_contact_name, emergency_contact_phone, preferred_radius already exist
            $table->string('location_city')->nullable()->after('referral_earnings');
            $table->string('location_state')->nullable()->after('location_city');
            $table->string('location_country')->nullable()->after('location_state');
            $table->json('preferred_industries')->nullable()->after('industries');
            $table->string('profile_photo_url')->nullable()->after('preferred_industries');
            $table->string('resume_url')->nullable()->after('profile_photo_url');
            $table->string('linkedin_url')->nullable()->after('resume_url');

            // Indexes
            $table->index('subscription_tier');
            $table->index('onboarding_completed');
            $table->index('identity_verified');
            $table->index('location_city');
            $table->index('referral_code');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('worker_profiles', function (Blueprint $table) {
            $table->dropIndex(['subscription_tier']);
            $table->dropIndex(['onboarding_completed']);
            $table->dropIndex(['identity_verified']);
            $table->dropIndex(['location_city']);
            $table->dropIndex(['referral_code']);

            $table->dropColumn([
                'onboarding_completed',
                'onboarding_step',
                'onboarding_completed_at',
                'identity_verified',
                'identity_verified_at',
                'identity_verification_method',
                'subscription_tier',
                'tier_expires_at',
                'tier_upgraded_at',
                'total_late_arrivals',
                'total_early_departures',
                'total_no_acknowledgments',
                'average_response_time_minutes',
                'total_earnings',
                'pending_earnings',
                'withdrawn_earnings',
                'average_hourly_earned',
                'referral_code',
                'referred_by',
                'total_referrals',
                'referral_earnings',
                'location_city',
                'location_state',
                'location_country',
                'preferred_industries',
                'profile_photo_url',
                'resume_url',
                'linkedin_url',
            ]);
        });
    }
}
