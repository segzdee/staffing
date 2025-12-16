<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * STAFF-REG-002: Worker Account Creation & Verification System
 *
 * Creates the referral_codes table for tracking worker referrals.
 */
return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('referral_codes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('code', 20)->unique();
            $table->string('type')->default('worker'); // worker, business, agency

            // Code settings
            $table->boolean('is_active')->default(true);
            $table->integer('max_uses')->nullable(); // null = unlimited
            $table->integer('uses_count')->default(0);
            $table->timestamp('expires_at')->nullable();

            // Reward configuration
            $table->decimal('referrer_reward_amount', 10, 2)->default(0);
            $table->string('referrer_reward_type')->default('cash'); // cash, credit, bonus
            $table->decimal('referee_reward_amount', 10, 2)->default(0);
            $table->string('referee_reward_type')->default('cash'); // cash, credit, bonus

            // Conditions for earning rewards
            $table->integer('referee_shifts_required')->default(1); // Shifts referee must complete
            $table->integer('referee_days_required')->nullable(); // Days referee must be active

            // Campaign tracking
            $table->string('campaign_name')->nullable();
            $table->string('campaign_source')->nullable();

            // Statistics
            $table->decimal('total_rewards_paid', 12, 2)->default(0);
            $table->integer('successful_conversions')->default(0);

            $table->timestamps();
            $table->softDeletes();

            // Indexes
            $table->index('code');
            $table->index('is_active');
            $table->index('expires_at');
            $table->index(['user_id', 'type']);
        });

        // Track individual referral usages
        Schema::create('referral_usages', function (Blueprint $table) {
            $table->id();
            $table->foreignId('referral_code_id')->constrained()->cascadeOnDelete();
            $table->foreignId('referrer_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('referee_id')->constrained('users')->cascadeOnDelete();

            // Status tracking
            $table->enum('status', ['pending', 'qualified', 'rewarded', 'expired', 'cancelled'])->default('pending');

            // Qualification tracking
            $table->integer('referee_shifts_completed')->default(0);
            $table->timestamp('qualification_met_at')->nullable();

            // Reward tracking
            $table->decimal('referrer_reward_paid', 10, 2)->default(0);
            $table->decimal('referee_reward_paid', 10, 2)->default(0);
            $table->timestamp('referrer_reward_paid_at')->nullable();
            $table->timestamp('referee_reward_paid_at')->nullable();

            // Registration metadata
            $table->string('registration_ip', 45)->nullable();
            $table->text('registration_user_agent')->nullable();

            $table->timestamps();

            // Indexes
            $table->index(['referrer_id', 'status']);
            $table->index(['referee_id', 'status']);
            $table->index('status');
            $table->unique(['referral_code_id', 'referee_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('referral_usages');
        Schema::dropIfExists('referral_codes');
    }
};
