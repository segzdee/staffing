<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * Onboarding Events Table
     * Audit trail for all onboarding-related events
     */
    public function up(): void
    {
        Schema::create('onboarding_events', function (Blueprint $table) {
            $table->id();

            // User reference
            $table->foreignId('user_id')->constrained()->onDelete('cascade');

            // Event details
            $table->string('event_type', 50)->comment('onboarding_started, step_started, step_completed, etc.');
            $table->string('step_id', 50)->nullable()->comment('Reference to step_id in onboarding_steps');

            // Event metadata
            $table->json('metadata')->nullable()->comment('Additional event data');

            // Source tracking
            $table->string('source', 50)->nullable()->comment('web, mobile, api, admin');
            $table->string('ip_address', 45)->nullable();
            $table->text('user_agent')->nullable();

            // Timing
            $table->unsignedInteger('duration_seconds')->nullable()->comment('Duration of the event/action');

            // Session tracking for funnel analysis
            $table->string('session_id', 100)->nullable();

            // A/B test tracking
            $table->string('cohort_id', 50)->nullable();
            $table->string('cohort_variant', 50)->nullable();

            // References
            $table->unsignedBigInteger('related_event_id')->nullable()->comment('Link to related event');

            $table->timestamp('created_at')->useCurrent();

            // Indexes for analytics
            $table->index(['user_id', 'event_type']);
            $table->index(['event_type', 'created_at']);
            $table->index(['step_id', 'event_type']);
            $table->index(['cohort_id', 'event_type']);
            $table->index('session_id');
            $table->index('created_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('onboarding_events');
    }
};
