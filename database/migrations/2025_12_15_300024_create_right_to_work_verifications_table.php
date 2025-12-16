<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * STAFF-REG-005: Right-to-Work Verification
 *
 * Creates the main table for tracking worker right-to-work verification status
 * across multiple jurisdictions (US, UK, EU, AU, UAE, Singapore).
 */
return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('right_to_work_verifications', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');

            // Jurisdiction and verification type
            $table->string('jurisdiction', 10)->index(); // US, UK, EU, AU, UAE, SG
            $table->string('verification_type', 50); // i9, rtw_check, work_permit, vevo, emirates_id, employment_pass

            // Status tracking
            $table->enum('status', [
                'pending',           // Initiated, awaiting documents
                'documents_submitted', // Documents uploaded, awaiting review
                'under_review',      // Being reviewed by admin/automated system
                'verified',          // Successfully verified
                'expired',           // Verification expired
                'rejected',          // Verification rejected
                'additional_docs_required', // More documents needed
            ])->default('pending');

            // Verification details
            $table->string('document_combination')->nullable(); // e.g., 'list_a' or 'list_b_c' for US I-9
            $table->date('verified_at')->nullable();
            $table->date('expires_at')->nullable()->index();
            $table->foreignId('verified_by')->nullable()->constrained('users');

            // For jurisdictions with online verification
            $table->string('online_verification_code')->nullable(); // UK share code, etc.
            $table->string('online_verification_reference')->nullable();
            $table->timestamp('online_verified_at')->nullable();

            // Work authorization details
            $table->boolean('has_work_restrictions')->default(false);
            $table->text('work_restrictions')->nullable(); // JSON: hours limit, job types, etc.
            $table->date('work_permit_expiry')->nullable();

            // Audit and compliance
            $table->text('verification_notes')->nullable();
            $table->text('rejection_reason')->nullable();
            $table->string('verification_method')->nullable(); // manual, automated, api
            $table->json('audit_log')->nullable(); // Track all status changes

            // Reminder tracking
            $table->tinyInteger('expiry_reminder_level')->default(0); // 0=none, 1=30d, 2=14d, 3=7d
            $table->timestamp('last_reminder_sent_at')->nullable();

            // Retention policy
            $table->date('retention_expires_at')->nullable(); // Data retention per jurisdiction
            $table->boolean('is_archived')->default(false);

            $table->timestamps();
            $table->softDeletes();

            // Indexes for common queries
            $table->index(['user_id', 'status']);
            $table->index(['jurisdiction', 'status']);
            $table->index(['expires_at', 'status']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('right_to_work_verifications');
    }
};
