<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * Onboarding Cohorts Table
     * A/B testing cohorts for onboarding experiments
     */
    public function up(): void
    {
        Schema::create('onboarding_cohorts', function (Blueprint $table) {
            $table->id();

            // Cohort identification
            $table->string('cohort_id', 50)->unique()->comment('Unique cohort identifier');
            $table->string('name', 100)->comment('Human-readable cohort name');
            $table->text('description')->nullable();

            // Experiment details
            $table->string('experiment_name', 100)->comment('Name of the A/B test experiment');
            $table->enum('user_type', ['worker', 'business', 'agency', 'all'])->default('all');

            // Variant configuration
            $table->string('variant', 50)->comment('control, variant_a, variant_b, etc.');
            $table->unsignedSmallInteger('allocation_percentage')->default(50)->comment('Traffic allocation 0-100');

            // Cohort period
            $table->timestamp('start_date')->nullable();
            $table->timestamp('end_date')->nullable();

            // Status
            $table->enum('status', ['draft', 'active', 'paused', 'completed', 'winner'])->default('draft');
            $table->boolean('is_winner')->default(false);

            // Performance metrics
            $table->unsignedInteger('total_users')->default(0);
            $table->unsignedInteger('completed_users')->default(0);
            $table->decimal('completion_rate', 5, 2)->default(0)->comment('0.00 to 100.00');
            $table->decimal('avg_time_to_activation_hours', 10, 2)->nullable();
            $table->decimal('dropout_rate', 5, 2)->default(0);

            // Step-specific metrics
            $table->json('step_completion_rates')->nullable()->comment('Completion rate per step');
            $table->json('step_dropout_rates')->nullable()->comment('Dropout rate per step');
            $table->json('step_avg_times')->nullable()->comment('Average time per step');

            // Statistical significance
            $table->decimal('statistical_significance', 5, 2)->nullable()->comment('P-value or confidence');
            $table->json('comparison_data')->nullable()->comment('Detailed comparison metrics');

            // Configuration
            $table->json('configuration')->nullable()->comment('Custom configuration for this cohort');

            // Audit
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('declared_winner_at')->nullable();

            $table->timestamps();

            // Indexes
            $table->index(['experiment_name', 'status']);
            $table->index(['user_type', 'status']);
            $table->index(['start_date', 'end_date']);
            $table->index('status');
        });

        // User cohort assignments
        Schema::create('onboarding_user_cohorts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->foreignId('onboarding_cohort_id')->constrained('onboarding_cohorts')->onDelete('cascade');
            $table->timestamp('assigned_at')->useCurrent();

            $table->unique(['user_id', 'onboarding_cohort_id']);
            $table->index('assigned_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('onboarding_user_cohorts');
        Schema::dropIfExists('onboarding_cohorts');
    }
};
