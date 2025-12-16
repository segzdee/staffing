<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * Onboarding Steps Configuration Table
     * Stores the master list of all onboarding steps for workers and businesses
     */
    public function up(): void
    {
        Schema::create('onboarding_steps', function (Blueprint $table) {
            $table->id();

            // Step identification
            $table->string('step_id', 50)->unique()->comment('Unique identifier: account_created, email_verified, etc.');
            $table->string('user_type', 20)->comment('worker, business, agency');

            // Step configuration
            $table->string('name', 100)->comment('Human-readable step name');
            $table->text('description')->nullable()->comment('Detailed description shown to users');
            $table->text('help_text')->nullable()->comment('Context-specific help content');
            $table->string('help_url')->nullable()->comment('Link to detailed help documentation');

            // Step categorization
            $table->enum('step_type', ['required', 'recommended', 'optional'])->default('required');
            $table->string('category', 50)->nullable()->comment('verification, profile, compliance, payment, etc.');

            // Ordering and dependencies
            $table->unsignedInteger('order')->default(0)->comment('Display order within type');
            $table->json('dependencies')->nullable()->comment('Array of step_ids that must be completed first');

            // Weight for progress calculation
            $table->unsignedInteger('weight')->default(10)->comment('Weight for progress percentage calculation');

            // Time estimates
            $table->unsignedInteger('estimated_minutes')->default(5)->comment('Estimated time to complete in minutes');

            // Thresholds and targets
            $table->unsignedInteger('threshold')->nullable()->comment('Minimum percentage required (e.g., profile 80% complete)');
            $table->unsignedInteger('target')->nullable()->comment('Target count for countable steps (e.g., 3 skills)');

            // Auto-completion
            $table->boolean('auto_complete')->default(false)->comment('Whether step can be auto-completed by system');
            $table->string('auto_complete_event')->nullable()->comment('Event that triggers auto-completion');

            // Routing
            $table->string('route_name')->nullable()->comment('Route to navigate user for completion');
            $table->json('route_params')->nullable()->comment('Parameters for the route');

            // UI configuration
            $table->string('icon')->nullable()->default('check-circle')->comment('Icon class for display');
            $table->string('color')->nullable()->default('blue')->comment('Color theme for step');

            // Status
            $table->boolean('is_active')->default(true);

            // A/B testing
            $table->string('cohort_variant')->nullable()->comment('A/B test variant this step belongs to');

            $table->timestamps();

            // Indexes
            $table->index(['user_type', 'step_type', 'order'], 'os_type_step_order_idx');
            $table->index(['user_type', 'is_active'], 'os_type_active_idx');
            $table->index('cohort_variant', 'os_cohort_variant_idx');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('onboarding_steps');
    }
};
