<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddBusinessManagementFieldsToBusinessProfilesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('business_profiles', function (Blueprint $table) {
            // BIZ-001: Onboarding & Verification
            $table->boolean('onboarding_completed')->default(false)->after('user_id');
            $table->integer('onboarding_step')->nullable()->after('onboarding_completed');
            $table->timestamp('onboarding_completed_at')->nullable()->after('onboarding_step');
            $table->string('verification_status')->default('pending')->after('is_verified');
            $table->text('verification_notes')->nullable()->after('verification_status');
            $table->string('business_license_url')->nullable()->after('verification_notes');
            $table->string('insurance_certificate_url')->nullable()->after('business_license_url');
            $table->string('tax_document_url')->nullable()->after('insurance_certificate_url');
            $table->timestamp('documents_submitted_at')->nullable()->after('tax_document_url');

            // BIZ-002: Venue Management
            $table->boolean('multi_location_enabled')->default(false)->after('total_locations');
            $table->integer('active_venues')->default(0)->after('multi_location_enabled');

            // BIZ-003: Shift Templates
            $table->integer('total_templates')->default(0)->after('active_venues');
            $table->integer('active_templates')->default(0)->after('total_templates');

            // BIZ-004: Ratings & Reviews
            $table->integer('total_reviews')->default(0)->after('rating_average');
            $table->decimal('communication_rating', 3, 2)->default(0.00)->after('total_reviews');
            $table->decimal('punctuality_rating', 3, 2)->default(0.00)->after('communication_rating');
            $table->decimal('professionalism_rating', 3, 2)->default(0.00)->after('punctuality_rating');

            // BIZ-005: Analytics & Performance
            $table->decimal('average_shift_cost', 10, 2)->default(0.00)->after('total_shifts_cancelled');
            $table->decimal('total_spent', 12, 2)->default(0.00)->after('average_shift_cost');
            $table->decimal('pending_payment', 12, 2)->default(0.00)->after('total_spent');
            $table->integer('unique_workers_hired')->default(0)->after('pending_payment');
            $table->integer('repeat_workers')->default(0)->after('unique_workers_hired');

            // BIZ-006: Billing & Subscription
            $table->enum('subscription_plan', ['free', 'basic', 'professional', 'enterprise'])->default('free')->after('repeat_workers');
            $table->timestamp('subscription_expires_at')->nullable()->after('subscription_plan');
            $table->decimal('monthly_credit_limit', 10, 2)->nullable()->after('subscription_expires_at');
            $table->decimal('monthly_credit_used', 10, 2)->default(0.00)->after('monthly_credit_limit');
            $table->boolean('autopay_enabled')->default(false)->after('has_payment_method');
            $table->string('default_payment_method_id')->nullable()->after('autopay_enabled');

            // BIZ-007: Worker Preferences
            $table->json('preferred_worker_ids')->nullable()->after('default_payment_method_id');
            $table->json('blacklisted_worker_ids')->nullable()->after('preferred_worker_ids');
            $table->boolean('allow_new_workers')->default(true)->after('blacklisted_worker_ids');
            $table->decimal('minimum_worker_rating', 3, 2)->default(0.00)->after('allow_new_workers');
            $table->integer('minimum_shifts_completed')->default(0)->after('minimum_worker_rating');

            // BIZ-008: Cancellation Management
            $table->decimal('cancellation_rate', 5, 2)->default(0.00)->after('fill_rate');
            $table->integer('late_cancellations')->default(0)->after('cancellation_rate');
            $table->decimal('total_cancellation_penalties', 10, 2)->default(0.00)->after('late_cancellations');

            // BIZ-009: Support & Communication
            $table->integer('open_support_tickets')->default(0)->after('total_cancellation_penalties');
            $table->timestamp('last_support_contact')->nullable()->after('open_support_tickets');
            $table->boolean('priority_support')->default(false)->after('last_support_contact');

            // BIZ-010: Compliance & Status
            $table->boolean('account_in_good_standing')->default(true)->after('priority_support');
            $table->text('account_warning_message')->nullable()->after('account_in_good_standing');
            $table->timestamp('last_shift_posted_at')->nullable()->after('account_warning_message');
            $table->boolean('can_post_shifts')->default(true)->after('last_shift_posted_at');

            // Indexes
            $table->index('subscription_plan');
            $table->index('verification_status');
            $table->index('onboarding_completed');
            $table->index('account_in_good_standing');
            $table->index('last_shift_posted_at');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('business_profiles', function (Blueprint $table) {
            $table->dropIndex(['subscription_plan']);
            $table->dropIndex(['verification_status']);
            $table->dropIndex(['onboarding_completed']);
            $table->dropIndex(['account_in_good_standing']);
            $table->dropIndex(['last_shift_posted_at']);

            $table->dropColumn([
                'onboarding_completed',
                'onboarding_step',
                'onboarding_completed_at',
                'verification_status',
                'verification_notes',
                'business_license_url',
                'insurance_certificate_url',
                'tax_document_url',
                'documents_submitted_at',
                'multi_location_enabled',
                'active_venues',
                'total_templates',
                'active_templates',
                'total_reviews',
                'communication_rating',
                'punctuality_rating',
                'professionalism_rating',
                'average_shift_cost',
                'total_spent',
                'pending_payment',
                'unique_workers_hired',
                'repeat_workers',
                'subscription_plan',
                'subscription_expires_at',
                'monthly_credit_limit',
                'monthly_credit_used',
                'autopay_enabled',
                'default_payment_method_id',
                'preferred_worker_ids',
                'blacklisted_worker_ids',
                'allow_new_workers',
                'minimum_worker_rating',
                'minimum_shifts_completed',
                'cancellation_rate',
                'late_cancellations',
                'total_cancellation_penalties',
                'open_support_tickets',
                'last_support_contact',
                'priority_support',
                'account_in_good_standing',
                'account_warning_message',
                'last_shift_posted_at',
                'can_post_shifts',
            ]);
        });
    }
}
