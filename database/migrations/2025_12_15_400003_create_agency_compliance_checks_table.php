<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * AGY-REG-001: Agency Registration & Onboarding System
 *
 * Creates the agency_compliance_checks table for tracking compliance
 * verification during agency registration and ongoing compliance monitoring.
 */
return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('agency_compliance_checks', function (Blueprint $table) {
            $table->id();
            $table->foreignId('application_id')->constrained('agency_applications')->cascadeOnDelete();

            // Type of compliance check
            $table->enum('check_type', [
                'business_license',       // Valid business operating license
                'insurance_coverage',     // Adequate insurance coverage verification
                'tax_compliance',         // Tax registration and compliance status
                'background_check',       // Background check on principals/directors
                'reference_verification', // Business/client reference verification
                'financial_stability',    // Financial health assessment
                'legal_standing',         // No pending litigation, good legal standing
                'regulatory_compliance',  // Industry-specific regulatory compliance
                'data_protection',        // GDPR/data protection compliance
                'employment_law',         // Employment law compliance verification
                'health_safety',          // Health and safety compliance
                'anti_money_laundering',  // AML/KYC compliance
                'sanctions_screening',    // Sanctions list screening
            ]);

            // Check status tracking
            $table->enum('status', [
                'pending',      // Check not yet started
                'in_progress',  // Check currently underway
                'passed',       // Check passed successfully
                'failed',       // Check failed
                'waived',       // Check waived (with justification)
                'expired',      // Previous check has expired, needs renewal
                'not_required', // Check not required for this tier/region
            ])->default('pending');

            // Examiner information
            $table->foreignId('checked_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('checked_at')->nullable();

            // Detailed notes and findings
            $table->text('notes')->nullable();
            $table->text('findings')->nullable();
            $table->text('failure_reason')->nullable();
            $table->text('waiver_justification')->nullable();

            // Third-party verification integration
            $table->string('external_provider')->nullable(); // e.g., "Checkr", "Onfido", "Experian"
            $table->string('external_reference_id')->nullable();
            $table->json('external_result')->nullable();
            $table->timestamp('external_checked_at')->nullable();

            // Risk assessment
            $table->enum('risk_level', ['low', 'medium', 'high', 'critical'])->nullable();
            $table->text('risk_notes')->nullable();

            // Expiration tracking for recurring checks
            $table->date('valid_until')->nullable();
            $table->boolean('renewal_required')->default(false);
            $table->timestamp('renewal_reminder_sent_at')->nullable();

            // Score/rating if applicable
            $table->decimal('score', 5, 2)->nullable(); // 0-100 scale
            $table->decimal('minimum_score_required', 5, 2)->nullable();

            // Document linkage (which documents support this check)
            $table->json('supporting_document_ids')->nullable();

            // Priority and SLA tracking
            $table->enum('priority', ['low', 'normal', 'high', 'urgent'])->default('normal');
            $table->timestamp('due_by')->nullable();
            $table->boolean('sla_breached')->default(false);

            $table->timestamps();
            $table->softDeletes();

            // Indexes for efficient querying
            $table->index('application_id');
            $table->index('check_type');
            $table->index('status');
            $table->index(['application_id', 'check_type']);
            $table->index(['application_id', 'status']);
            $table->index('valid_until');
            $table->index('risk_level');
            $table->index('external_reference_id');

            // Unique constraint: one active check of each type per application
            $table->unique(['application_id', 'check_type'], 'unique_application_check_type');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('agency_compliance_checks');
    }
};
