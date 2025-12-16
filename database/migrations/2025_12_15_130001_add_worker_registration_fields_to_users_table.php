<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * STAFF-REG-002: Worker Account Creation & Verification System
 *
 * Adds worker-specific registration fields to the users table.
 */
return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            // Phone verification fields
            if (!Schema::hasColumn('users', 'phone')) {
                $table->string('phone', 20)->nullable()->after('email');
            }
            if (!Schema::hasColumn('users', 'phone_verified_at')) {
                $table->timestamp('phone_verified_at')->nullable()->after('phone');
            }
            if (!Schema::hasColumn('users', 'phone_country_code')) {
                $table->string('phone_country_code', 5)->nullable()->after('phone');
            }

            // Social authentication fields
            if (!Schema::hasColumn('users', 'social_provider')) {
                $table->string('social_provider', 20)->nullable()->after('phone_verified_at');
            }
            if (!Schema::hasColumn('users', 'social_id')) {
                $table->string('social_id')->nullable()->after('social_provider');
            }
            if (!Schema::hasColumn('users', 'social_avatar')) {
                $table->string('social_avatar')->nullable()->after('social_id');
            }

            // Registration metadata
            if (!Schema::hasColumn('users', 'registration_method')) {
                $table->enum('registration_method', ['email', 'phone', 'google', 'apple', 'facebook'])->default('email')->after('social_avatar');
            }
            if (!Schema::hasColumn('users', 'registration_ip')) {
                $table->string('registration_ip', 45)->nullable()->after('registration_method');
            }
            if (!Schema::hasColumn('users', 'registration_user_agent')) {
                $table->text('registration_user_agent')->nullable()->after('registration_ip');
            }
            if (!Schema::hasColumn('users', 'registration_completed_at')) {
                $table->timestamp('registration_completed_at')->nullable()->after('registration_user_agent');
            }

            // Referral tracking
            if (!Schema::hasColumn('users', 'referred_by_code')) {
                $table->string('referred_by_code', 20)->nullable()->after('registration_completed_at');
            }
            if (!Schema::hasColumn('users', 'referred_by_user_id')) {
                $table->foreignId('referred_by_user_id')->nullable()->after('referred_by_code')->constrained('users')->nullOnDelete();
            }

            // Agency invitation tracking
            if (!Schema::hasColumn('users', 'invited_by_agency_id')) {
                $table->foreignId('invited_by_agency_id')->nullable()->after('referred_by_user_id')->constrained('users')->nullOnDelete();
            }
            if (!Schema::hasColumn('users', 'agency_invitation_token')) {
                $table->string('agency_invitation_token', 64)->nullable()->after('invited_by_agency_id');
            }

            // Terms acceptance
            if (!Schema::hasColumn('users', 'terms_accepted_at')) {
                $table->timestamp('terms_accepted_at')->nullable()->after('agency_invitation_token');
            }
            if (!Schema::hasColumn('users', 'privacy_accepted_at')) {
                $table->timestamp('privacy_accepted_at')->nullable()->after('terms_accepted_at');
            }
            if (!Schema::hasColumn('users', 'marketing_consent')) {
                $table->boolean('marketing_consent')->default(false)->after('privacy_accepted_at');
            }

            // Account flags
            if (!Schema::hasColumn('users', 'is_profile_complete')) {
                $table->boolean('is_profile_complete')->default(false)->after('marketing_consent');
            }
            if (!Schema::hasColumn('users', 'requires_password_change')) {
                $table->boolean('requires_password_change')->default(false)->after('is_profile_complete');
            }

            // Indexes for common queries (with try-catch to handle re-runs)
            try {
                $table->index('phone');
            } catch (\Exception $e) {
                // Index already exists
            }

            try {
                $table->index(['social_provider', 'social_id']);
            } catch (\Exception $e) {
                // Index already exists
            }

            try {
                $table->index('referred_by_code');
            } catch (\Exception $e) {
                // Index already exists
            }

            try {
                $table->index('registration_method');
            } catch (\Exception $e) {
                // Index already exists
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            // Drop indexes first
            $table->dropIndex(['phone']);
            $table->dropIndex(['social_provider', 'social_id']);
            $table->dropIndex(['referred_by_code']);
            $table->dropIndex(['registration_method']);

            // Drop foreign keys
            $table->dropForeign(['referred_by_user_id']);
            $table->dropForeign(['invited_by_agency_id']);

            // Drop columns
            $table->dropColumn([
                'phone',
                'phone_verified_at',
                'phone_country_code',
                'social_provider',
                'social_id',
                'social_avatar',
                'registration_method',
                'registration_ip',
                'registration_user_agent',
                'registration_completed_at',
                'referred_by_code',
                'referred_by_user_id',
                'invited_by_agency_id',
                'agency_invitation_token',
                'terms_accepted_at',
                'privacy_accepted_at',
                'marketing_consent',
                'is_profile_complete',
                'requires_password_change',
            ]);
        });
    }
};
