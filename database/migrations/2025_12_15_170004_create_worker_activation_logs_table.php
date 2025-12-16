<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     * STAFF-REG-010: Worker Activation Tracking
     */
    public function up(): void
    {
        Schema::create('worker_activation_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');

            // Activation status
            $table->enum('status', ['pending', 'eligible', 'activated', 'suspended', 'deactivated'])->default('pending');

            // Eligibility check results
            $table->json('eligibility_checks')->nullable(); // Detailed check results
            $table->boolean('all_required_complete')->default(false);
            $table->integer('required_steps_complete')->default(0);
            $table->integer('required_steps_total')->default(0);
            $table->integer('recommended_steps_complete')->default(0);
            $table->integer('recommended_steps_total')->default(0);

            // Profile metrics at activation
            $table->decimal('profile_completeness', 5, 2)->default(0);
            $table->integer('skills_count')->default(0);
            $table->integer('certifications_count')->default(0);

            // Initial assignments
            $table->string('initial_tier')->nullable(); // Bronze, Silver, Gold, Platinum
            $table->decimal('initial_reliability_score', 5, 2)->nullable();

            // Referral tracking
            $table->string('referral_code_used')->nullable();
            $table->foreignId('referred_by_user_id')->nullable()->constrained('users');
            $table->decimal('referral_bonus_amount', 10, 2)->nullable();
            $table->boolean('referral_bonus_processed')->default(false);
            $table->timestamp('referral_bonus_processed_at')->nullable();

            // Activation details
            $table->timestamp('activated_at')->nullable();
            $table->foreignId('activated_by')->nullable()->constrained('users'); // Admin who activated
            $table->text('activation_notes')->nullable();

            // Analytics
            $table->integer('days_to_activation')->nullable(); // Days from registration to activation
            $table->string('activation_source')->nullable(); // 'self', 'admin', 'auto'

            $table->timestamps();

            // Indexes
            $table->unique('user_id');
            $table->index('status');
            $table->index('activated_at');
            $table->index('referral_code_used');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('worker_activation_logs');
    }
};
