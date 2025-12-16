<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * STAFF-REG-006: Adjudication Cases
 *
 * Manages the review workflow for background checks with "consider" results.
 * Supports FCRA-compliant adverse action process.
 */
return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('adjudication_cases', function (Blueprint $table) {
            $table->id();
            $table->foreignId('background_check_id')
                ->constrained()
                ->onDelete('cascade');
            $table->foreignId('user_id')->constrained()->onDelete('cascade'); // The worker

            // Case details
            $table->string('case_number')->unique();
            $table->enum('case_type', [
                'criminal_record',
                'identity_mismatch',
                'employment_discrepancy',
                'education_discrepancy',
                'motor_vehicle',
                'sex_offender',
                'other',
            ]);

            // Status workflow
            $table->enum('status', [
                'open',                     // Newly created
                'under_review',             // Being reviewed by adjudicator
                'pending_worker_response',  // Waiting for worker input
                'pre_adverse_action',       // Pre-adverse action notice sent
                'waiting_period',           // Mandatory waiting period (FCRA)
                'final_review',             // Final review after waiting period
                'approved',                 // Approved to proceed
                'adverse_action',           // Adverse action taken
                'closed',                   // Case closed (either direction)
                'escalated',                // Escalated to senior review
            ])->default('open');

            // Assignment
            $table->foreignId('assigned_to')->nullable()->constrained('users');
            $table->timestamp('assigned_at')->nullable();
            $table->foreignId('escalated_to')->nullable()->constrained('users');
            $table->timestamp('escalated_at')->nullable();

            // Findings
            $table->text('findings_encrypted')->nullable(); // Encrypted details of issues found
            $table->json('record_details')->nullable(); // Non-PII details (dates, types, jurisdictions)
            $table->enum('severity', ['low', 'medium', 'high', 'critical'])->nullable();

            // Worker response
            $table->text('worker_response')->nullable();
            $table->json('worker_documents')->nullable(); // References to uploaded documents
            $table->timestamp('worker_responded_at')->nullable();

            // Decision
            $table->enum('decision', [
                'pending',
                'approved',
                'conditionally_approved',
                'denied',
            ])->default('pending');
            $table->text('decision_rationale')->nullable();
            $table->foreignId('decided_by')->nullable()->constrained('users');
            $table->timestamp('decided_at')->nullable();

            // Conditional approval details
            $table->json('conditions')->nullable(); // Any conditions for approval
            $table->date('review_date')->nullable(); // When to review again if conditional

            // FCRA adverse action timeline
            $table->timestamp('pre_adverse_notice_sent_at')->nullable();
            $table->date('waiting_period_ends_at')->nullable(); // Typically 5 business days
            $table->timestamp('final_notice_sent_at')->nullable();

            // Communications
            $table->json('communications_log')->nullable(); // All communications with worker

            // SLA tracking
            $table->timestamp('sla_deadline')->nullable();
            $table->boolean('sla_breached')->default(false);

            // Audit
            $table->json('audit_log')->nullable();

            $table->timestamps();
            $table->softDeletes();

            // Indexes
            $table->index(['status', 'assigned_to']);
            $table->index(['user_id', 'status']);
            $table->index(['sla_deadline', 'status']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('adjudication_cases');
    }
};
