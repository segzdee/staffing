<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * BIZ-REG-008: Team Activity Tracking
 *
 * Comprehensive activity log for team member actions.
 * Supports audit trails and activity reporting.
 */
class CreateTeamActivitiesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('team_activities', function (Blueprint $table) {
            $table->id();

            // Business context
            $table->foreignId('business_id')->constrained('users')->onDelete('cascade');

            // Who performed the action
            $table->foreignId('user_id')->constrained('users')->onDelete('cascade');

            // Team member record (if applicable)
            $table->foreignId('team_member_id')->nullable()->constrained('team_members')->onDelete('set null');

            // Activity type
            $table->string('activity_type'); // invitation_sent, invitation_accepted, role_changed, suspended, reactivated, removed, shift_posted, etc.

            // Subject of the action (who/what was affected)
            $table->string('subject_type')->nullable(); // User, TeamMember, Shift, Venue, etc.
            $table->unsignedBigInteger('subject_id')->nullable();

            // Description
            $table->string('description');

            // Activity details as JSON
            $table->json('metadata')->nullable(); // Store old/new values, additional context

            // IP and user agent for security audit
            $table->string('ip_address')->nullable();
            $table->string('user_agent')->nullable();

            // Venue context (if action was venue-specific)
            $table->foreignId('venue_id')->nullable()->constrained('venues')->onDelete('set null');

            $table->timestamps();

            // Indexes for querying
            $table->index('business_id');
            $table->index('user_id');
            $table->index('team_member_id');
            $table->index('activity_type');
            $table->index(['subject_type', 'subject_id']);
            $table->index('venue_id');
            $table->index('created_at');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('team_activities');
    }
}
