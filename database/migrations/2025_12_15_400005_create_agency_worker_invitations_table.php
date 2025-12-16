<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * AGY-REG-001: Agency Registration & Onboarding System
 *
 * Creates the agency_worker_invitations table for tracking worker invitations
 * during the agency onboarding phase (worker_onboarding status).
 *
 * Note: This is distinct from the existing agency_invitations table which handles
 * more complex invitation workflows. This table is specifically for the initial
 * worker pool setup during agency registration.
 */
return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('agency_worker_invitations', function (Blueprint $table) {
            $table->id();

            // Link to the agency (user_id of agency account)
            $table->foreignId('agency_id')->constrained('users')->cascadeOnDelete();

            // Optionally link to application (during registration phase)
            $table->foreignId('application_id')->nullable()->constrained('agency_applications')->nullOnDelete();

            // Invitee information
            $table->string('worker_email');
            $table->string('worker_name')->nullable();
            $table->string('worker_phone', 20)->nullable();

            // Invitation token for acceptance
            $table->string('invitation_token', 64)->unique();
            $table->timestamp('token_expires_at');

            // Invitation status
            $table->enum('status', [
                'pending',   // Invitation sent, awaiting response
                'accepted',  // Worker accepted and registered/linked
                'declined',  // Worker explicitly declined
                'expired',   // Invitation expired without response
                'cancelled', // Agency cancelled the invitation
                'bounced',   // Email bounced/undeliverable
            ])->default('pending');

            // Timestamps for tracking
            $table->timestamp('invited_at');
            $table->timestamp('sent_at')->nullable(); // Email actually sent
            $table->timestamp('viewed_at')->nullable(); // Invitation link clicked
            $table->timestamp('accepted_at')->nullable();
            $table->timestamp('declined_at')->nullable();

            // Link to created/linked worker account
            $table->foreignId('worker_user_id')->nullable()->constrained('users')->nullOnDelete();

            // Invitation message customization
            $table->text('personal_message')->nullable();

            // Pre-configured worker settings
            $table->decimal('preset_commission_rate', 5, 2)->nullable();
            $table->json('preset_skills')->nullable();
            $table->json('preset_tags')->nullable();

            // Reminder tracking
            $table->unsignedTinyInteger('reminder_count')->default(0);
            $table->timestamp('last_reminder_at')->nullable();

            // Tracking metadata
            $table->string('invitation_source')->default('manual'); // manual, import, api
            $table->string('import_batch_id')->nullable(); // For bulk imports
            $table->string('invited_by_ip', 45)->nullable();
            $table->string('accepted_ip', 45)->nullable();
            $table->text('accepted_user_agent')->nullable();

            $table->timestamps();
            $table->softDeletes();

            // Indexes for efficient querying
            $table->index('agency_id');
            $table->index('application_id');
            $table->index('worker_email');
            $table->index('invitation_token');
            $table->index('status');
            $table->index('invited_at');
            $table->index('token_expires_at');
            $table->index(['agency_id', 'status']);
            $table->index(['agency_id', 'worker_email']);
            $table->index('import_batch_id');

            // Prevent duplicate invitations to same email from same agency
            $table->unique(['agency_id', 'worker_email', 'status'], 'unique_active_invitation');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('agency_worker_invitations');
    }
};
