<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * WKR-014: Team Formation - Team Shift Requests
 *
 * Creates team_shift_requests table for teams to apply to shifts collectively.
 * When a team applies, the leader submits the request and members confirm participation.
 */
return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('team_shift_requests', function (Blueprint $table) {
            $table->id();

            // Team applying for the shift
            $table->foreignId('team_id')->constrained('worker_teams')->onDelete('cascade');

            // Shift being applied for
            $table->foreignId('shift_id')->constrained()->onDelete('cascade');

            // Who submitted the team application
            $table->foreignId('requested_by')->constrained('users')->onDelete('cascade');

            // Request status
            $table->enum('status', [
                'pending',      // Waiting for business approval
                'approved',     // Business approved the team
                'rejected',     // Business rejected the team
                'cancelled',    // Team cancelled the request
                'expired',      // Request expired before decision
                'partial',      // Some members confirmed, others haven't
            ])->default('pending');

            // How many team members are needed for this shift
            $table->integer('members_needed')->default(1);

            // How many team members have confirmed participation
            $table->integer('members_confirmed')->default(0);

            // Array of confirmed member user IDs
            $table->json('confirmed_members')->nullable();

            // Array of assigned member user IDs (after approval)
            $table->json('assigned_members')->nullable();

            // Application message from team leader
            $table->text('application_message')->nullable();

            // Business response message
            $table->text('response_message')->nullable();

            // Response tracking
            $table->foreignId('responded_by')->nullable()->constrained('users')->onDelete('set null');
            $table->timestamp('responded_at')->nullable();

            // Deadline for member confirmations
            $table->timestamp('confirmation_deadline')->nullable();

            // Priority indicator (e.g., team has worked with this business before)
            $table->integer('priority_score')->default(0);

            $table->timestamps();

            // Unique constraint: one active request per team per shift
            $table->unique(['team_id', 'shift_id'], 'team_shift_unique');

            // Indexes
            $table->index(['shift_id', 'status']);
            $table->index(['team_id', 'status']);
            $table->index('requested_by');
            $table->index('status');
            $table->index('priority_score');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('team_shift_requests');
    }
};
