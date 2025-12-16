<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * BIZ-REG-008: Team Invitations
 *
 * Separate table for tracking team invitations.
 * Allows invitations to non-existent users (invite by email).
 */
class CreateTeamInvitationsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('team_invitations', function (Blueprint $table) {
            $table->id();

            // Business sending the invitation
            $table->foreignId('business_id')->constrained('users')->onDelete('cascade');

            // Who sent the invitation
            $table->foreignId('invited_by')->constrained('users')->onDelete('cascade');

            // Email address (used for inviting non-users)
            $table->string('email');

            // If user exists, link to them
            $table->foreignId('user_id')->nullable()->constrained('users')->onDelete('cascade');

            // Assigned role
            $table->enum('role', [
                'admin',
                'manager',
                'scheduler',
                'viewer'
            ])->default('viewer');

            // Venue access restrictions (null = all venues)
            $table->json('venue_access')->nullable();

            // Custom permissions override (null = use role defaults)
            $table->json('custom_permissions')->nullable();

            // Invitation token
            $table->string('token', 64)->unique();
            $table->string('token_hash')->unique(); // Hashed version for lookup

            // Status
            $table->enum('status', [
                'pending',
                'accepted',
                'declined',
                'expired',
                'revoked'
            ])->default('pending');

            // Timestamps
            $table->timestamp('expires_at');
            $table->timestamp('accepted_at')->nullable();
            $table->timestamp('declined_at')->nullable();
            $table->timestamp('revoked_at')->nullable();

            // Personal message from inviter
            $table->text('message')->nullable();

            // Revocation reason
            $table->string('revocation_reason')->nullable();

            // Tracking
            $table->integer('resend_count')->default(0);
            $table->timestamp('last_resent_at')->nullable();
            $table->string('accepted_ip')->nullable();
            $table->string('accepted_user_agent')->nullable();

            $table->timestamps();

            // Indexes
            $table->index('business_id');
            $table->index('email');
            $table->index('user_id');
            $table->index('status');
            $table->index('token_hash');
            $table->index('expires_at');

            // Prevent duplicate pending invitations
            $table->unique(['business_id', 'email', 'status'], 'unique_pending_invitation');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('team_invitations');
    }
}
