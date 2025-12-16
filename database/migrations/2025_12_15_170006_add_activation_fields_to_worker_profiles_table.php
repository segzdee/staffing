<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     * STAFF-REG-010: Add activation and onboarding fields to worker_profiles
     */
    public function up(): void
    {
        Schema::table('worker_profiles', function (Blueprint $table) {
            // Activation status
            if (!Schema::hasColumn('worker_profiles', 'is_activated')) {
                $table->boolean('is_activated')->default(false)->after('onboarding_completed');
            }
            if (!Schema::hasColumn('worker_profiles', 'activated_at')) {
                $table->timestamp('activated_at')->nullable()->after('is_activated');
            }

            // Matching eligibility
            if (!Schema::hasColumn('worker_profiles', 'is_matching_eligible')) {
                $table->boolean('is_matching_eligible')->default(false)->after('activated_at');
            }
            if (!Schema::hasColumn('worker_profiles', 'matching_eligibility_reason')) {
                $table->string('matching_eligibility_reason')->nullable()->after('is_matching_eligible');
            }

            // Phone verification (if not exists)
            if (!Schema::hasColumn('worker_profiles', 'phone_verified')) {
                $table->boolean('phone_verified')->default(false)->after('phone');
            }
            if (!Schema::hasColumn('worker_profiles', 'phone_verified_at')) {
                $table->timestamp('phone_verified_at')->nullable()->after('phone_verified');
            }

            // RTW (Right to Work) verification
            if (!Schema::hasColumn('worker_profiles', 'rtw_verified')) {
                $table->boolean('rtw_verified')->default(false)->after('identity_verified_at');
            }
            if (!Schema::hasColumn('worker_profiles', 'rtw_verified_at')) {
                $table->timestamp('rtw_verified_at')->nullable()->after('rtw_verified');
            }
            if (!Schema::hasColumn('worker_profiles', 'rtw_document_type')) {
                $table->string('rtw_document_type')->nullable()->after('rtw_verified_at');
            }
            if (!Schema::hasColumn('worker_profiles', 'rtw_document_url')) {
                $table->string('rtw_document_url')->nullable()->after('rtw_document_type');
            }
            if (!Schema::hasColumn('worker_profiles', 'rtw_expiry_date')) {
                $table->date('rtw_expiry_date')->nullable()->after('rtw_document_url');
            }

            // Payment setup status
            if (!Schema::hasColumn('worker_profiles', 'payment_setup_complete')) {
                $table->boolean('payment_setup_complete')->default(false)->after('total_earnings');
            }
            if (!Schema::hasColumn('worker_profiles', 'payment_setup_at')) {
                $table->timestamp('payment_setup_at')->nullable()->after('payment_setup_complete');
            }

            // First shift guidance
            if (!Schema::hasColumn('worker_profiles', 'first_shift_guidance_shown')) {
                $table->boolean('first_shift_guidance_shown')->default(false);
            }
            if (!Schema::hasColumn('worker_profiles', 'first_shift_completed_at')) {
                $table->timestamp('first_shift_completed_at')->nullable();
            }

            // Profile photo approval status
            if (!Schema::hasColumn('worker_profiles', 'profile_photo_status')) {
                $table->enum('profile_photo_status', ['pending', 'approved', 'rejected', 'none'])->default('none');
            }
            if (!Schema::hasColumn('worker_profiles', 'profile_photo_rejected_reason')) {
                $table->string('profile_photo_rejected_reason')->nullable();
            }

            // Onboarding analytics
            if (!Schema::hasColumn('worker_profiles', 'onboarding_started_at')) {
                $table->timestamp('onboarding_started_at')->nullable();
            }
            if (!Schema::hasColumn('worker_profiles', 'onboarding_last_step_at')) {
                $table->timestamp('onboarding_last_step_at')->nullable();
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('worker_profiles', function (Blueprint $table) {
            $columns = [
                'is_activated',
                'activated_at',
                'is_matching_eligible',
                'matching_eligibility_reason',
                'phone_verified',
                'phone_verified_at',
                'rtw_verified',
                'rtw_verified_at',
                'rtw_document_type',
                'rtw_document_url',
                'rtw_expiry_date',
                'payment_setup_complete',
                'payment_setup_at',
                'first_shift_guidance_shown',
                'first_shift_completed_at',
                'profile_photo_status',
                'profile_photo_rejected_reason',
                'onboarding_started_at',
                'onboarding_last_step_at',
            ];

            foreach ($columns as $column) {
                if (Schema::hasColumn('worker_profiles', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }
};
