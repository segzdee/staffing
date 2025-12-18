<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * WKR-014: Team Formation - Worker Team Members
 *
 * Creates worker_team_members table to track membership in worker teams.
 * Each worker can be a leader or member of multiple teams.
 */
return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('worker_team_members', function (Blueprint $table) {
            $table->id();

            // Team reference
            $table->foreignId('team_id')->constrained('worker_teams')->onDelete('cascade');

            // Worker/User reference
            $table->foreignId('user_id')->constrained()->onDelete('cascade');

            // Role in the team
            $table->enum('role', ['leader', 'member'])->default('member');

            // Invitation status
            $table->enum('status', ['pending', 'active', 'declined', 'removed'])->default('pending');

            // Who invited this member (null for team creator)
            $table->foreignId('invited_by')->nullable()->constrained('users')->onDelete('set null');

            // Timestamps for status changes
            $table->timestamp('invited_at')->nullable();
            $table->timestamp('joined_at')->nullable();
            $table->timestamp('left_at')->nullable();

            // Contribution tracking
            $table->integer('shifts_with_team')->default(0);
            $table->integer('earnings_with_team')->default(0); // In cents

            // Notes (e.g., reason for removal)
            $table->text('notes')->nullable();

            $table->timestamps();

            // Unique constraint: one membership per user per team
            $table->unique(['team_id', 'user_id']);

            // Indexes
            $table->index(['team_id', 'status']);
            $table->index(['user_id', 'status']);
            $table->index('role');
            $table->index('invited_by');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('worker_team_members');
    }
};
