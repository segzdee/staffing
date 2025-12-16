<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     * BIZ-REG-002: Business Referral Tracking
     */
    public function up(): void
    {
        Schema::create('business_referrals', function (Blueprint $table) {
            $table->id();

            // Referrer (existing business)
            $table->foreignId('referrer_business_id')->constrained('business_profiles')->onDelete('cascade');
            $table->foreignId('referrer_user_id')->constrained('users')->onDelete('cascade');

            // Referred (new business)
            $table->foreignId('referred_business_id')->nullable()->constrained('business_profiles')->onDelete('set null');
            $table->foreignId('referred_user_id')->nullable()->constrained('users')->onDelete('set null');

            // Referral Details
            $table->string('referral_code', 20)->unique();
            $table->string('referred_email');
            $table->string('referred_company_name')->nullable();

            // Status Tracking
            $table->enum('status', [
                'pending',        // Invitation sent
                'clicked',        // Link clicked
                'registered',     // Account created
                'activated',      // Profile completed & activated
                'first_shift',    // First shift posted
                'qualified',      // Met reward threshold
                'rewarded',       // Reward issued
                'expired',        // Invitation expired
                'cancelled'       // Cancelled by referrer
            ])->default('pending');

            // Reward Tracking
            $table->boolean('reward_eligible')->default(false);
            $table->decimal('referrer_reward_amount', 10, 2)->nullable();
            $table->decimal('referred_reward_amount', 10, 2)->nullable();
            $table->string('reward_type')->nullable(); // credit, discount, cash
            $table->timestamp('reward_issued_at')->nullable();
            $table->string('reward_transaction_id')->nullable();

            // Qualification Requirements
            $table->integer('required_shifts_posted')->default(1);
            $table->integer('actual_shifts_posted')->default(0);
            $table->decimal('required_spend_amount', 10, 2)->nullable();
            $table->decimal('actual_spend_amount', 10, 2)->default(0);
            $table->integer('qualification_days')->default(30);
            $table->timestamp('qualification_deadline')->nullable();

            // Tracking
            $table->timestamp('invitation_sent_at')->nullable();
            $table->timestamp('link_clicked_at')->nullable();
            $table->timestamp('registered_at')->nullable();
            $table->timestamp('activated_at')->nullable();
            $table->timestamp('first_shift_at')->nullable();
            $table->timestamp('qualified_at')->nullable();

            // Communication
            $table->integer('reminder_count')->default(0);
            $table->timestamp('last_reminder_at')->nullable();

            // Attribution
            $table->string('ip_address')->nullable();
            $table->string('user_agent')->nullable();

            $table->timestamps();
            $table->softDeletes();

            // Indexes
            $table->index('referrer_business_id');
            $table->index('referred_business_id');
            $table->index('referral_code');
            $table->index('referred_email');
            $table->index('status');
            $table->index('reward_eligible');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('business_referrals');
    }
};
