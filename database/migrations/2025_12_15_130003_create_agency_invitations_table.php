<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * STAFF-REG-002: Worker Account Creation & Verification System
 *
 * Creates the agency_invitations table for tracking agency worker invitations.
 */
return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('agency_invitations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('agency_id')->constrained('users')->cascadeOnDelete();
            $table->string('token', 64)->unique();

            // Invitation details
            $table->string('email')->nullable();
            $table->string('phone', 20)->nullable();
            $table->string('name')->nullable();

            // Invitation type
            $table->enum('type', ['email', 'phone', 'link', 'bulk'])->default('email');

            // Status tracking
            $table->enum('status', ['pending', 'sent', 'viewed', 'accepted', 'expired', 'cancelled'])->default('pending');

            // Expiration
            $table->timestamp('expires_at');
            $table->timestamp('sent_at')->nullable();
            $table->timestamp('viewed_at')->nullable();
            $table->timestamp('accepted_at')->nullable();

            // If accepted, link to the created user
            $table->foreignId('accepted_by_user_id')->nullable()->constrained('users')->nullOnDelete();

            // Pre-set commission rate for this worker when they join
            $table->decimal('preset_commission_rate', 5, 2)->nullable();

            // Pre-assigned skills/certifications
            $table->json('preset_skills')->nullable();
            $table->json('preset_certifications')->nullable();

            // Message customization
            $table->text('personal_message')->nullable();

            // Bulk invitation tracking
            $table->string('batch_id')->nullable();

            // Tracking metadata
            $table->string('invitation_ip', 45)->nullable();
            $table->string('accepted_ip', 45)->nullable();
            $table->text('accepted_user_agent')->nullable();

            $table->timestamps();
            $table->softDeletes();

            // Indexes
            $table->index('token');
            $table->index('email');
            $table->index('phone');
            $table->index('status');
            $table->index('expires_at');
            $table->index(['agency_id', 'status']);
            $table->index('batch_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('agency_invitations');
    }
};
