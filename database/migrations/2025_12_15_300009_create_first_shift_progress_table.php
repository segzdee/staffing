<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Migration: First Shift Progress Tracking
 *
 * BIZ-REG-009: First Shift Wizard
 *
 * Tracks business progress through the first shift wizard
 * and stores saved draft data for continuing later.
 */
return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('first_shift_progress', function (Blueprint $table) {
            $table->id();
            $table->foreignId('business_profile_id')->constrained()->onDelete('cascade');

            // Wizard completion status
            $table->boolean('wizard_completed')->default(false);
            $table->timestamp('wizard_completed_at')->nullable();
            $table->unsignedTinyInteger('current_step')->default(1); // Steps 1-6
            $table->unsignedTinyInteger('highest_step_reached')->default(1);

            // Step completion tracking
            $table->boolean('step_1_venue_complete')->default(false);
            $table->boolean('step_2_role_complete')->default(false);
            $table->boolean('step_3_schedule_complete')->default(false);
            $table->boolean('step_4_rate_complete')->default(false);
            $table->boolean('step_5_details_complete')->default(false);
            $table->boolean('step_6_review_complete')->default(false);

            // Step timestamps
            $table->timestamp('step_1_completed_at')->nullable();
            $table->timestamp('step_2_completed_at')->nullable();
            $table->timestamp('step_3_completed_at')->nullable();
            $table->timestamp('step_4_completed_at')->nullable();
            $table->timestamp('step_5_completed_at')->nullable();
            $table->timestamp('step_6_completed_at')->nullable();

            // Draft data storage (JSON for flexibility)
            $table->json('draft_data')->nullable();

            // Selected values
            $table->foreignId('selected_venue_id')->nullable()->constrained('venues')->nullOnDelete();
            $table->string('selected_role')->nullable();
            $table->date('selected_date')->nullable();
            $table->time('selected_start_time')->nullable();
            $table->time('selected_end_time')->nullable();
            $table->integer('selected_hourly_rate')->nullable(); // In cents
            $table->integer('selected_workers_needed')->default(1);

            // Mode selection
            $table->string('posting_mode')->default('detailed'); // quick, detailed

            // Template option
            $table->boolean('save_as_template')->default(false);
            $table->string('template_name')->nullable();

            // First shift reference
            $table->foreignId('first_shift_id')->nullable()->constrained('shifts')->nullOnDelete();

            // Analytics
            $table->integer('total_time_spent_seconds')->default(0);
            $table->integer('session_count')->default(0);
            $table->timestamp('last_activity_at')->nullable();

            // Promotional credits applied
            $table->boolean('promo_applied')->default(false);
            $table->string('promo_code')->nullable();
            $table->integer('promo_discount_cents')->default(0);

            $table->timestamps();

            // Indexes
            $table->index('business_profile_id');
            $table->index('wizard_completed');
            $table->index('current_step');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('first_shift_progress');
    }
};
