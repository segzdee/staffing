<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * STAFF-REG-003 & 004: Worker Profile & KYC Fields
 *
 * Extends worker_profiles table with additional fields for profile creation
 * and identity verification tracking.
 */
return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('worker_profiles', function (Blueprint $table) {
            // STAFF-REG-003: Additional Profile Fields
            // Name fields (for more granular control)
            if (!Schema::hasColumn('worker_profiles', 'first_name')) {
                $table->string('first_name', 100)->nullable()->after('user_id');
            }
            if (!Schema::hasColumn('worker_profiles', 'last_name')) {
                $table->string('last_name', 100)->nullable()->after('first_name');
            }
            if (!Schema::hasColumn('worker_profiles', 'middle_name')) {
                $table->string('middle_name', 100)->nullable()->after('last_name');
            }
            if (!Schema::hasColumn('worker_profiles', 'preferred_name')) {
                $table->string('preferred_name', 100)->nullable()->after('middle_name');
            }
            if (!Schema::hasColumn('worker_profiles', 'gender')) {
                $table->enum('gender', ['male', 'female', 'non_binary', 'prefer_not_to_say', 'other'])
                    ->nullable()->after('date_of_birth');
            }

            // Profile Photo Validation
            if (!Schema::hasColumn('worker_profiles', 'profile_photo_verified')) {
                $table->boolean('profile_photo_verified')->default(false)->after('profile_photo_url');
            }
            if (!Schema::hasColumn('worker_profiles', 'profile_photo_face_detected')) {
                $table->boolean('profile_photo_face_detected')->default(false)->after('profile_photo_verified');
            }
            if (!Schema::hasColumn('worker_profiles', 'profile_photo_face_confidence')) {
                $table->decimal('profile_photo_face_confidence', 5, 4)->nullable()->after('profile_photo_face_detected');
            }
            if (!Schema::hasColumn('worker_profiles', 'profile_photo_updated_at')) {
                $table->timestamp('profile_photo_updated_at')->nullable()->after('profile_photo_face_confidence');
            }

            // STAFF-REG-004: KYC Status Fields
            if (!Schema::hasColumn('worker_profiles', 'kyc_status')) {
                $table->enum('kyc_status', [
                    'not_started',
                    'pending',
                    'in_progress',
                    'manual_review',
                    'approved',
                    'rejected',
                    'expired'
                ])->default('not_started')->after('identity_verification_method');
            }
            if (!Schema::hasColumn('worker_profiles', 'kyc_level')) {
                $table->enum('kyc_level', ['none', 'basic', 'standard', 'enhanced'])
                    ->default('none')->after('kyc_status');
            }
            if (!Schema::hasColumn('worker_profiles', 'kyc_expires_at')) {
                $table->timestamp('kyc_expires_at')->nullable()->after('kyc_level');
            }
            if (!Schema::hasColumn('worker_profiles', 'kyc_verification_id')) {
                $table->unsignedBigInteger('kyc_verification_id')->nullable()->after('kyc_expires_at');
            }

            // Verified Personal Data (from KYC)
            if (!Schema::hasColumn('worker_profiles', 'verified_first_name')) {
                $table->string('verified_first_name', 100)->nullable()->after('kyc_verification_id');
            }
            if (!Schema::hasColumn('worker_profiles', 'verified_last_name')) {
                $table->string('verified_last_name', 100)->nullable()->after('verified_first_name');
            }
            if (!Schema::hasColumn('worker_profiles', 'verified_date_of_birth')) {
                $table->date('verified_date_of_birth')->nullable()->after('verified_last_name');
            }
            if (!Schema::hasColumn('worker_profiles', 'verified_nationality')) {
                $table->string('verified_nationality', 3)->nullable()->after('verified_date_of_birth');
            }

            // Age Verification
            if (!Schema::hasColumn('worker_profiles', 'age_verified')) {
                $table->boolean('age_verified')->default(false)->after('verified_nationality');
            }
            if (!Schema::hasColumn('worker_profiles', 'age_verified_at')) {
                $table->timestamp('age_verified_at')->nullable()->after('age_verified');
            }
            if (!Schema::hasColumn('worker_profiles', 'minimum_working_age_met')) {
                $table->boolean('minimum_working_age_met')->default(false)->after('age_verified_at');
            }

            // Location Geocoding
            if (!Schema::hasColumn('worker_profiles', 'geocoded_address')) {
                $table->text('geocoded_address')->nullable()->after('location_country');
            }
            if (!Schema::hasColumn('worker_profiles', 'geocoded_at')) {
                $table->timestamp('geocoded_at')->nullable()->after('geocoded_address');
            }
            if (!Schema::hasColumn('worker_profiles', 'timezone')) {
                $table->string('timezone', 50)->nullable()->after('geocoded_at');
            }

            // Profile Completion Tracking
            if (!Schema::hasColumn('worker_profiles', 'profile_completion_percentage')) {
                $table->tinyInteger('profile_completion_percentage')->default(0)->after('is_complete');
            }
            if (!Schema::hasColumn('worker_profiles', 'profile_sections_completed')) {
                $table->json('profile_sections_completed')->nullable()->after('profile_completion_percentage');
            }
            if (!Schema::hasColumn('worker_profiles', 'profile_last_updated_at')) {
                $table->timestamp('profile_last_updated_at')->nullable()->after('profile_sections_completed');
            }

            // Work Eligibility
            if (!Schema::hasColumn('worker_profiles', 'work_eligibility_status')) {
                $table->enum('work_eligibility_status', [
                    'not_checked',
                    'pending',
                    'eligible',
                    'ineligible',
                    'requires_sponsorship'
                ])->default('not_checked')->after('minimum_working_age_met');
            }
            if (!Schema::hasColumn('worker_profiles', 'work_eligibility_countries')) {
                $table->json('work_eligibility_countries')->nullable()->after('work_eligibility_status');
            }
        });

        // Add foreign key constraint separately to handle existing data
        Schema::table('worker_profiles', function (Blueprint $table) {
            if (Schema::hasColumn('worker_profiles', 'kyc_verification_id')) {
                $table->foreign('kyc_verification_id')
                    ->references('id')
                    ->on('identity_verifications')
                    ->nullOnDelete();
            }
        });

        // Add additional indexes
        Schema::table('worker_profiles', function (Blueprint $table) {
            $table->index('kyc_status');
            $table->index('kyc_level');
            $table->index('kyc_expires_at');
            $table->index('age_verified');
            $table->index('profile_completion_percentage');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('worker_profiles', function (Blueprint $table) {
            // Drop foreign key first
            if (Schema::hasColumn('worker_profiles', 'kyc_verification_id')) {
                $table->dropForeign(['kyc_verification_id']);
            }

            // Drop indexes
            $table->dropIndex(['kyc_status']);
            $table->dropIndex(['kyc_level']);
            $table->dropIndex(['kyc_expires_at']);
            $table->dropIndex(['age_verified']);
            $table->dropIndex(['profile_completion_percentage']);

            // Drop columns
            $table->dropColumn([
                'first_name',
                'last_name',
                'middle_name',
                'preferred_name',
                'gender',
                'profile_photo_verified',
                'profile_photo_face_detected',
                'profile_photo_face_confidence',
                'profile_photo_updated_at',
                'kyc_status',
                'kyc_level',
                'kyc_expires_at',
                'kyc_verification_id',
                'verified_first_name',
                'verified_last_name',
                'verified_date_of_birth',
                'verified_nationality',
                'age_verified',
                'age_verified_at',
                'minimum_working_age_met',
                'geocoded_address',
                'geocoded_at',
                'timezone',
                'profile_completion_percentage',
                'profile_sections_completed',
                'profile_last_updated_at',
                'work_eligibility_status',
                'work_eligibility_countries',
            ]);
        });
    }
};
