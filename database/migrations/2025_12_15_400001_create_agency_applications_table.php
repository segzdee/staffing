<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * AGY-REG-001: Agency Registration & Onboarding System
 *
 * Creates the agency_applications table for managing agency registration
 * workflow with a 14-state approval process.
 */
return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('agency_applications', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();

            // Application status with 14 states for multi-phase approval workflow
            $table->enum('status', [
                'draft',                // Initial state, application not yet submitted
                'submitted',            // Application submitted, awaiting review
                'document_review',      // Documents being reviewed
                'document_approved',    // Documents passed review
                'document_rejected',    // Documents failed review
                'compliance_review',    // Compliance checks in progress
                'compliance_approved',  // Compliance checks passed
                'compliance_rejected',  // Compliance checks failed
                'commercial_review',    // Commercial terms under review
                'commercial_approved',  // Commercial terms approved
                'commercial_rejected',  // Commercial terms rejected
                'worker_onboarding',    // Initial worker pool setup phase
                'approved',             // Fully approved, agency active
                'rejected',             // Final rejection
            ])->default('draft');

            // Partnership tier determines features and pricing
            $table->enum('partnership_tier', [
                'standard',     // Basic features, standard commission rates
                'professional', // Enhanced features, volume discounts
                'enterprise',   // Full features, custom terms, dedicated support
            ])->default('standard');

            // Business information (JSON for flexibility)
            $table->json('business_info')->nullable()->comment('Company name, registration number, tax ID, founding date, employee count, etc.');

            // Contact information (JSON for multiple contacts)
            $table->json('contact_info')->nullable()->comment('Primary contact, billing contact, operations contact, etc.');

            // Application reference number for tracking
            $table->string('application_reference', 32)->unique();

            // Submission tracking
            $table->timestamp('submitted_at')->nullable();
            $table->timestamp('reviewed_at')->nullable();
            $table->foreignId('reviewer_id')->nullable()->constrained('users')->nullOnDelete();

            // Rejection handling
            $table->text('rejection_reason')->nullable();
            $table->timestamp('rejection_date')->nullable();
            $table->boolean('can_reapply')->default(true);
            $table->timestamp('reapply_after')->nullable();

            // Approval tracking
            $table->timestamp('approved_at')->nullable();
            $table->foreignId('approved_by')->nullable()->constrained('users')->nullOnDelete();

            // Phase completion timestamps for audit trail
            $table->timestamp('document_review_completed_at')->nullable();
            $table->timestamp('compliance_review_completed_at')->nullable();
            $table->timestamp('commercial_review_completed_at')->nullable();
            $table->timestamp('worker_onboarding_completed_at')->nullable();

            // Internal notes and priority
            $table->text('internal_notes')->nullable();
            $table->enum('priority', ['low', 'normal', 'high', 'urgent'])->default('normal');

            // Source tracking for marketing attribution
            $table->string('referral_source')->nullable();
            $table->string('referral_code')->nullable();

            // IP and device tracking for fraud prevention
            $table->string('submission_ip', 45)->nullable();
            $table->text('submission_user_agent')->nullable();

            $table->timestamps();
            $table->softDeletes();

            // Indexes for efficient querying
            $table->index('status');
            $table->index('partnership_tier');
            $table->index('application_reference');
            $table->index('submitted_at');
            $table->index('reviewer_id');
            $table->index(['status', 'partnership_tier']);
            $table->index(['status', 'submitted_at']);
            $table->index('priority');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('agency_applications');
    }
};
