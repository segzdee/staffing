<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * WKR-014: Team Formation - Worker Teams
 *
 * Creates worker_teams table for workers to form teams that can apply
 * to shifts together. Teams are worker-managed groups for collective
 * shift applications and scheduling.
 *
 * Note: This is different from business team_members which is for
 * business staff permissions (BIZ-003).
 */
return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('worker_teams', function (Blueprint $table) {
            $table->id();

            // Team name
            $table->string('name');

            // Team leader/creator
            $table->foreignId('created_by')->constrained('users')->onDelete('cascade');

            // Optional association with a business (for preferred teams)
            $table->foreignId('business_id')->nullable()->constrained('users')->onDelete('set null');

            // Team description
            $table->text('description')->nullable();

            // Team avatar/logo URL
            $table->string('avatar_url')->nullable();

            // Maximum number of members allowed
            $table->integer('max_members')->default(10);

            // Current member count (denormalized for performance)
            $table->integer('member_count')->default(1);

            // Team status
            $table->boolean('is_active')->default(true);

            // Privacy settings
            $table->boolean('is_public')->default(false); // Public teams can be discovered
            $table->boolean('requires_approval')->default(true); // New members need leader approval

            // Team statistics
            $table->integer('total_shifts_completed')->default(0);
            $table->decimal('average_rating', 3, 2)->nullable();
            $table->integer('total_earnings')->default(0); // In cents

            // Specializations/skills the team focuses on
            $table->json('specializations')->nullable();

            // Preferred industries for the team
            $table->json('preferred_industries')->nullable();

            // Minimum reliability score to join (optional)
            $table->decimal('min_reliability_score', 5, 2)->nullable();

            $table->timestamps();
            $table->softDeletes();

            // Indexes
            $table->index('created_by');
            $table->index('business_id');
            $table->index('is_active');
            $table->index('is_public');
            $table->index(['is_active', 'is_public'], 'worker_teams_active_public_idx');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('worker_teams');
    }
};
