<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * Onboarding Progress Table
     * Tracks each user's progress through onboarding steps
     */
    public function up(): void
    {
        Schema::create('onboarding_progress', function (Blueprint $table) {
            $table->id();

            // User reference
            $table->foreignId('user_id')->constrained()->onDelete('cascade');

            // Step reference
            $table->foreignId('onboarding_step_id')->constrained('onboarding_steps')->onDelete('cascade');

            // Status tracking
            $table->enum('status', ['pending', 'in_progress', 'completed', 'failed', 'skipped'])->default('pending');

            // Progress data
            $table->unsignedInteger('progress_percentage')->default(0)->comment('0-100 for partial completion');
            $table->json('progress_data')->nullable()->comment('Additional step-specific progress data');

            // Time tracking
            $table->timestamp('started_at')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->unsignedInteger('time_spent_seconds')->default(0)->comment('Total time spent on step');
            $table->unsignedSmallInteger('attempt_count')->default(0)->comment('Number of attempts');

            // Completion details
            $table->string('completed_by')->nullable()->comment('user, system, admin, auto');
            $table->text('completion_notes')->nullable();

            // Skip details
            $table->timestamp('skipped_at')->nullable();
            $table->string('skip_reason')->nullable();

            // Failure details
            $table->timestamp('failed_at')->nullable();
            $table->text('failure_reason')->nullable();

            // Reminder tracking
            $table->timestamp('last_reminder_at')->nullable();
            $table->unsignedSmallInteger('reminder_count')->default(0);

            $table->timestamps();

            // Unique constraint - one progress record per user per step
            $table->unique(['user_id', 'onboarding_step_id'], 'op_user_step_unique');

            // Indexes
            $table->index(['user_id', 'status'], 'op_user_status_idx');
            $table->index(['status', 'started_at'], 'op_status_started_idx');
            $table->index('completed_at', 'op_completed_at_idx');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('onboarding_progress');
    }
};
