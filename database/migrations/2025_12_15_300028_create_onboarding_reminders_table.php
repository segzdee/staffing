<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * Onboarding Reminders Table
     * Tracks scheduled and sent reminders for onboarding completion
     */
    public function up(): void
    {
        Schema::create('onboarding_reminders', function (Blueprint $table) {
            $table->id();

            // User reference
            $table->foreignId('user_id')->constrained()->onDelete('cascade');

            // Reminder type
            $table->enum('reminder_type', [
                'welcome',           // Initial welcome after signup
                'first_step',        // Reminder to start first step
                'incomplete_step',   // Specific step incomplete
                'inactivity',        // General inactivity reminder
                'milestone',         // Progress milestone encouragement
                'completion_nudge',  // Almost done, final push
                'celebration',       // Completion celebration
                'special_offer',     // Incentive to complete
                'support_offer',     // Offering help
            ])->default('incomplete_step');

            // Step reference (if step-specific)
            $table->string('step_id', 50)->nullable();

            // Scheduling
            $table->timestamp('scheduled_at');
            $table->timestamp('sent_at')->nullable();
            $table->timestamp('opened_at')->nullable();
            $table->timestamp('clicked_at')->nullable();

            // Status
            $table->enum('status', ['scheduled', 'sent', 'delivered', 'opened', 'clicked', 'cancelled', 'failed'])->default('scheduled');

            // Channel
            $table->enum('channel', ['email', 'push', 'sms', 'in_app'])->default('email');

            // Content
            $table->string('subject')->nullable();
            $table->text('message')->nullable();
            $table->json('template_data')->nullable();

            // Tracking
            $table->string('tracking_id', 100)->nullable()->unique();

            // Response
            $table->timestamp('user_responded_at')->nullable();
            $table->string('response_action')->nullable()->comment('completed_step, visited_dashboard, etc.');

            // Suppression
            $table->boolean('is_suppressed')->default(false);
            $table->string('suppression_reason')->nullable();

            $table->timestamps();

            // Indexes
            $table->index(['user_id', 'status']);
            $table->index(['scheduled_at', 'status']);
            $table->index(['reminder_type', 'status']);
            $table->index('step_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('onboarding_reminders');
    }
};
