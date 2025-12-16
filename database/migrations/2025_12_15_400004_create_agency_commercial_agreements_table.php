<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * AGY-REG-001: Agency Registration & Onboarding System
 *
 * Creates the agency_commercial_agreements table for managing
 * contracts, commission rates, and e-signature tracking.
 */
return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('agency_commercial_agreements', function (Blueprint $table) {
            $table->id();
            $table->foreignId('application_id')->constrained('agency_applications')->cascadeOnDelete();

            // Agreement type categorization
            $table->enum('agreement_type', [
                'master_service_agreement', // Main service agreement
                'commission_schedule',      // Commission rate schedule
                'data_processing',          // Data processing agreement (GDPR)
                'non_disclosure',           // NDA/confidentiality agreement
                'service_level',            // SLA terms
                'payment_terms',            // Payment and invoicing terms
                'insurance_requirement',    // Insurance requirements acknowledgment
                'code_of_conduct',          // Platform code of conduct
                'worker_protection',        // Worker protection terms
                'amendment',                // Amendment to existing agreement
            ]);

            // Commission structure
            $table->decimal('commission_rate', 5, 2)->nullable(); // Base commission percentage
            $table->json('tiered_commission')->nullable()->comment('Volume-based commission tiers');
            $table->json('special_rates')->nullable()->comment('Special rates for specific industries/shifts');

            // Contract terms (JSON for flexibility)
            $table->json('contract_terms')->nullable()->comment('Full contract terms and conditions');

            // Agreement version control
            $table->string('version', 20)->default('1.0');
            $table->foreignId('replaces_agreement_id')->nullable()->constrained('agency_commercial_agreements')->nullOnDelete();

            // Document storage
            $table->string('agreement_url')->nullable(); // URL to generated PDF
            $table->string('agreement_disk', 50)->default('s3');
            $table->string('agreement_path')->nullable();
            $table->string('document_hash', 64)->nullable(); // For integrity verification

            // Agreement status
            $table->enum('status', [
                'draft',     // Agreement being prepared
                'pending',   // Awaiting signature
                'signed',    // Fully executed
                'expired',   // Agreement has expired
                'terminated', // Agreement terminated
                'superseded', // Replaced by newer version
            ])->default('draft');

            // E-signature tracking
            $table->timestamp('sent_for_signature_at')->nullable();
            $table->timestamp('signed_at')->nullable();
            $table->json('signature_data')->nullable()->comment('E-signature details: signer info, IP, device, signature image ref');
            $table->string('signer_name')->nullable();
            $table->string('signer_email')->nullable();
            $table->string('signer_title')->nullable();
            $table->string('signing_ip', 45)->nullable();
            $table->text('signing_user_agent')->nullable();

            // E-signature provider integration
            $table->string('esign_provider')->nullable(); // e.g., "DocuSign", "HelloSign", "internal"
            $table->string('esign_envelope_id')->nullable();
            $table->string('esign_document_id')->nullable();
            $table->json('esign_metadata')->nullable();

            // Witness/counter-signature (if required)
            $table->foreignId('countersigned_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('countersigned_at')->nullable();
            $table->string('countersigner_name')->nullable();
            $table->string('countersigner_title')->nullable();

            // Agreement validity
            $table->date('effective_date')->nullable();
            $table->date('expiry_date')->nullable();
            $table->boolean('auto_renew')->default(false);
            $table->integer('renewal_notice_days')->default(30);
            $table->timestamp('renewal_reminder_sent_at')->nullable();

            // Termination tracking
            $table->timestamp('terminated_at')->nullable();
            $table->foreignId('terminated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->text('termination_reason')->nullable();

            // Internal tracking
            $table->text('internal_notes')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();

            $table->timestamps();
            $table->softDeletes();

            // Indexes for efficient querying
            $table->index('application_id');
            $table->index('agreement_type');
            $table->index('status');
            $table->index('signed_at');
            $table->index('expiry_date');
            $table->index(['application_id', 'agreement_type']);
            $table->index(['application_id', 'status']);
            $table->index('esign_envelope_id');
            $table->index('document_hash');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('agency_commercial_agreements');
    }
};
