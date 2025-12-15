<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * BIZ-003: Team & Permission Management
 *
 * Creates team_members table to allow businesses to invite and manage team members
 * with different roles and permissions for managing shifts, venues, and workers.
 *
 * Role Hierarchy:
 * - owner: Full access to everything (business owner)
 * - administrator: Full access except billing and team deletion
 * - location_manager: Manage specific venues/locations
 * - scheduler: Create and manage shifts only
 * - viewer: Read-only access to shifts and workers
 */
class CreateTeamMembersTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('team_members', function (Blueprint $table) {
            $table->id();

            // Relationships
            $table->foreignId('business_id')->constrained('users')->onDelete('cascade');
            $table->foreignId('user_id')->constrained('users')->onDelete('cascade');
            $table->foreignId('invited_by')->nullable()->constrained('users')->onDelete('set null');

            // Role and permissions
            $table->enum('role', [
                'owner',
                'administrator',
                'location_manager',
                'scheduler',
                'viewer'
            ])->default('viewer');

            // Venue access control (JSON array of venue IDs)
            // If null, member has access to all venues
            // If array, member only has access to specified venues
            $table->json('venue_access')->nullable();

            // Permission flags (granular control)
            $table->boolean('can_post_shifts')->default(false);
            $table->boolean('can_edit_shifts')->default(false);
            $table->boolean('can_cancel_shifts')->default(false);
            $table->boolean('can_approve_applications')->default(false);
            $table->boolean('can_manage_workers')->default(false);
            $table->boolean('can_view_payments')->default(false);
            $table->boolean('can_process_payments')->default(false);
            $table->boolean('can_manage_venues')->default(false);
            $table->boolean('can_manage_team')->default(false);
            $table->boolean('can_view_analytics')->default(false);
            $table->boolean('can_manage_settings')->default(false);

            // Invitation status
            $table->enum('status', [
                'pending',
                'active',
                'suspended',
                'revoked'
            ])->default('pending');

            $table->string('invitation_token')->nullable()->unique();
            $table->timestamp('invited_at')->nullable();
            $table->timestamp('invitation_expires_at')->nullable();
            $table->timestamp('joined_at')->nullable();

            // Activity tracking
            $table->timestamp('last_active_at')->nullable();
            $table->integer('shifts_posted')->default(0);
            $table->integer('shifts_edited')->default(0);
            $table->integer('applications_processed')->default(0);

            // Notes and reason for role
            $table->text('notes')->nullable();
            $table->string('revocation_reason')->nullable();
            $table->timestamp('revoked_at')->nullable();

            $table->timestamps();

            // Indexes
            $table->index('business_id');
            $table->index('user_id');
            $table->index('role');
            $table->index('status');
            $table->index('invitation_token');

            // Unique constraint: One user can only be a team member once per business
            $table->unique(['business_id', 'user_id']);
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('team_members');
    }
}
