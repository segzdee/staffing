<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Migration: Add Activation Tracking to Business Profiles
 *
 * BIZ-REG-011: Business Account Activation
 *
 * Adds activation tracking fields to monitor business account activation status
 * and requirements completion for shift posting eligibility.
 */
return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('business_profiles', function (Blueprint $table) {
            // Activation status tracking
            $table->boolean('activation_checked')->default(false)->after('can_post_shifts');
            $table->timestamp('activation_checked_at')->nullable()->after('activation_checked');
            $table->timestamp('last_activation_check')->nullable()->after('activation_checked_at');

            // Requirement completion tracking
            $table->json('activation_requirements_status')->nullable()->after('last_activation_check');
            /*
             * Structure:
             * {
             *   "email_verified": {"met": true, "checked_at": "2025-01-01 00:00:00"},
             *   "profile_complete": {"met": true, "checked_at": "2025-01-01 00:00:00"},
             *   "kyb_verified": {"met": false, "checked_at": "2025-01-01 00:00:00"},
             *   "insurance_verified": {"met": false, "checked_at": "2025-01-01 00:00:00"},
             *   "venue_created": {"met": true, "checked_at": "2025-01-01 00:00:00"},
             *   "payment_verified": {"met": false, "checked_at": "2025-01-01 00:00:00"}
             * }
             */

            // Activation gate information
            $table->integer('activation_completion_percentage')->default(0)->after('activation_requirements_status');
            $table->integer('activation_requirements_met')->default(0)->after('activation_completion_percentage');
            $table->integer('activation_requirements_total')->default(6)->after('activation_requirements_met');

            // Activation blocked reasons
            $table->json('activation_blocked_reasons')->nullable()->after('activation_requirements_total');
            $table->text('activation_notes')->nullable()->after('activation_blocked_reasons');

            // Indexes for performance
            $table->index('activation_checked');
            $table->index('activation_checked_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('business_profiles', function (Blueprint $table) {
            $table->dropIndex(['activation_checked']);
            $table->dropIndex(['activation_checked_at']);

            $table->dropColumn([
                'activation_checked',
                'activation_checked_at',
                'last_activation_check',
                'activation_requirements_status',
                'activation_completion_percentage',
                'activation_requirements_met',
                'activation_requirements_total',
                'activation_blocked_reasons',
                'activation_notes',
            ]);
        });
    }
};
