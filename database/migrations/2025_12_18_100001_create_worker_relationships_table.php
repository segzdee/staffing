<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * WKR-014: Team Formation - Worker Relationships
 *
 * Creates worker_relationships table for the buddy system and preferred coworker matching.
 * Allows workers to designate buddies, preferred coworkers, avoided workers, and mentor relationships.
 *
 * Relationship Types:
 * - buddy: Close working partner, prioritized for same-shift assignments
 * - preferred: Worker prefers to work with this person
 * - avoided: Worker prefers not to work with this person
 * - mentor: Senior worker mentoring this worker
 * - mentee: Worker being mentored by this worker
 */
return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('worker_relationships', function (Blueprint $table) {
            $table->id();

            // Worker initiating the relationship
            $table->foreignId('worker_id')->constrained('users')->onDelete('cascade');

            // Worker being related to
            $table->foreignId('related_worker_id')->constrained('users')->onDelete('cascade');

            // Type of relationship
            $table->enum('relationship_type', ['buddy', 'preferred', 'avoided', 'mentor', 'mentee']);

            // Collaboration metrics
            $table->integer('shifts_together')->default(0);
            $table->decimal('compatibility_score', 5, 2)->nullable(); // 0-100 score

            // Additional context
            $table->text('notes')->nullable();

            // Whether both workers have confirmed this relationship
            $table->boolean('is_mutual')->default(false);

            // For buddies: when the relationship was confirmed by the other party
            $table->timestamp('confirmed_at')->nullable();

            // Request status for buddy relationships
            $table->enum('status', ['pending', 'active', 'declined', 'removed'])->default('pending');

            // Last interaction/calculation timestamp
            $table->timestamp('last_calculated_at')->nullable();

            $table->timestamps();

            // Unique constraint: one relationship type per worker pair
            $table->unique(['worker_id', 'related_worker_id', 'relationship_type'], 'worker_rel_unique');

            // Indexes for efficient queries
            $table->index(['worker_id', 'relationship_type'], 'worker_relationships_worker_type_idx');
            $table->index(['related_worker_id', 'relationship_type'], 'worker_relationships_related_type_idx');
            $table->index(['worker_id', 'status'], 'worker_relationships_worker_status_idx');
            $table->index('compatibility_score');
            $table->index('shifts_together');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('worker_relationships');
    }
};
