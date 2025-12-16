<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * STAFF-REG-006: Background Checks
 *
 * Tracks background check status and results from various providers
 * (Checkr for US, DBS for UK, police clearance for others).
 */
return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('background_checks', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');

            // Provider and jurisdiction
            $table->string('jurisdiction', 10)->index(); // US, UK, AU, etc.
            $table->string('provider', 50); // checkr, dbs, police_clearance, etc.
            $table->string('provider_candidate_id')->nullable(); // External candidate ID
            $table->string('provider_report_id')->nullable()->index(); // External report ID

            // Check type
            // US: ssn_trace, criminal, sex_offender, motor_vehicle, education, employment
            // UK: dbs_basic, dbs_standard, dbs_enhanced, dbs_enhanced_barred
            // Other: police_clearance, criminal_record_check
            $table->string('check_type', 50);
            $table->json('check_components')->nullable(); // List of checks included

            // Status
            $table->enum('status', [
                'pending_consent',      // Awaiting worker consent
                'consent_received',     // Consent received, not yet submitted
                'submitted',            // Submitted to provider
                'processing',           // Being processed by provider
                'complete',             // Completed - clear or consider
                'consider',             // Completed with items to review
                'suspended',            // Check suspended
                'cancelled',            // Cancelled
                'expired',              // Results expired
                'dispute',              // Under dispute
            ])->default('pending_consent');

            // Results
            $table->enum('result', [
                'clear',                // No issues found
                'consider',             // Items found, requires review
                'fail',                 // Failed criteria
                'pending',              // Not yet complete
            ])->nullable();

            // Adjudication (for consider results)
            $table->enum('adjudication_status', [
                'not_applicable',
                'pending',
                'in_review',
                'approved',
                'denied',
            ])->default('not_applicable');
            $table->foreignId('adjudicated_by')->nullable()->constrained('users');
            $table->timestamp('adjudicated_at')->nullable();
            $table->text('adjudication_notes')->nullable();

            // Adverse action tracking (FCRA compliance)
            $table->boolean('adverse_action_required')->default(false);
            $table->timestamp('pre_adverse_action_sent_at')->nullable();
            $table->timestamp('pre_adverse_action_deadline')->nullable(); // Usually 5 days
            $table->timestamp('adverse_action_sent_at')->nullable();

            // Timing
            $table->timestamp('submitted_at')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->timestamp('expires_at')->nullable()->index(); // When results expire

            // Cost tracking
            $table->unsignedInteger('cost_cents')->nullable();
            $table->string('cost_currency', 3)->default('USD');
            $table->foreignId('billed_to')->nullable()->constrained('users'); // Business or agency

            // Encrypted result data
            $table->text('result_data_encrypted')->nullable(); // Full results from provider
            $table->text('report_url_encrypted')->nullable(); // Link to full report

            // Webhooks and sync
            $table->timestamp('last_webhook_at')->nullable();
            $table->string('last_webhook_event')->nullable();
            $table->json('webhook_log')->nullable();

            // Audit
            $table->json('audit_log')->nullable();

            $table->timestamps();
            $table->softDeletes();

            // Indexes
            $table->index(['user_id', 'status']);
            $table->index(['provider', 'provider_report_id']);
            $table->index(['jurisdiction', 'check_type']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('background_checks');
    }
};
