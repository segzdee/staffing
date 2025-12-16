<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     * BIZ-REG-002: Business Onboarding Progress Tracking
     */
    public function up(): void
    {
        Schema::create('business_onboarding', function (Blueprint $table) {
            $table->id();
            $table->foreignId('business_profile_id')->constrained('business_profiles')->onDelete('cascade');
            $table->foreignId('user_id')->constrained('users')->onDelete('cascade');

            // Overall Progress
            $table->integer('current_step')->default(1);
            $table->integer('total_steps')->default(6);
            $table->decimal('completion_percentage', 5, 2)->default(0);
            $table->enum('status', ['not_started', 'in_progress', 'pending_review', 'completed', 'suspended'])->default('not_started');

            // Step Completion Tracking (JSON for flexibility)
            $table->json('steps_completed')->nullable();
            /*
             * Structure:
             * {
             *   "account_created": {"completed": true, "completed_at": "2025-01-01 00:00:00"},
             *   "email_verified": {"completed": true, "completed_at": "2025-01-01 00:00:00"},
             *   "company_info": {"completed": false, "completed_at": null},
             *   "contact_info": {"completed": false, "completed_at": null},
             *   "address_info": {"completed": false, "completed_at": null},
             *   "payment_setup": {"completed": false, "completed_at": null}
             * }
             */

            // Profile Completion Tracking
            $table->decimal('profile_completion_score', 5, 2)->default(0);
            $table->json('missing_fields')->nullable();
            $table->json('optional_fields_completed')->nullable();

            // Registration Source Tracking
            $table->enum('signup_source', ['organic', 'referral', 'sales_assisted', 'partnership', 'advertising'])->default('organic');
            $table->string('referral_code')->nullable();
            $table->foreignId('referred_by_business_id')->nullable()->constrained('business_profiles')->onDelete('set null');
            $table->string('sales_rep_id')->nullable();
            $table->string('utm_source')->nullable();
            $table->string('utm_medium')->nullable();
            $table->string('utm_campaign')->nullable();

            // Email Domain Tracking (for duplicate detection)
            $table->string('email_domain')->nullable();

            // Activation Requirements
            $table->boolean('email_verified')->default(false);
            $table->boolean('profile_minimum_met')->default(false); // 80% completion
            $table->boolean('terms_accepted')->default(false);
            $table->timestamp('terms_accepted_at')->nullable();
            $table->string('terms_version')->nullable();
            $table->boolean('payment_method_added')->default(false);
            $table->boolean('is_activated')->default(false);
            $table->timestamp('activated_at')->nullable();

            // Reminders
            $table->timestamp('last_reminder_sent_at')->nullable();
            $table->integer('reminders_sent_count')->default(0);
            $table->timestamp('next_reminder_at')->nullable();

            // Time Tracking
            $table->timestamp('started_at')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->integer('time_to_complete_minutes')->nullable();

            $table->timestamps();

            // Indexes
            $table->index('business_profile_id');
            $table->index('user_id');
            $table->index('status');
            $table->index('email_domain');
            $table->index('signup_source');
            $table->index('referral_code');
            $table->index('is_activated');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('business_onboarding');
    }
};
