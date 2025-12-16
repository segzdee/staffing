<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * STAFF-REG-004: Identity Verification (KYC) - Main Verification Table
 *
 * Stores identity verification records with Onfido/Jumio integration.
 */
return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('identity_verifications', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->onDelete('cascade');

            // External Provider Information
            $table->string('provider')->default('onfido'); // onfido, jumio
            $table->string('provider_applicant_id')->nullable(); // Onfido applicant ID
            $table->string('provider_check_id')->nullable(); // Onfido check ID
            $table->string('provider_report_id')->nullable(); // Onfido report ID

            // Verification Status
            $table->enum('status', [
                'pending',           // Initial state, waiting for user to start
                'awaiting_input',    // User needs to submit documents
                'processing',        // Documents submitted, being processed
                'manual_review',     // Failed auto-verification, needs human review
                'approved',          // Verification successful
                'rejected',          // Verification failed
                'expired',           // Verification expired, needs renewal
                'cancelled'          // User or admin cancelled
            ])->default('pending');

            // Verification Level (for multi-tier KYC)
            $table->enum('verification_level', [
                'basic',        // Name + DOB verification
                'standard',     // Basic + ID document
                'enhanced'      // Standard + liveness check + address proof
            ])->default('standard');

            // Document Types Submitted
            $table->json('document_types')->nullable(); // ['passport', 'driving_license', 'national_id']

            // Verification Results
            $table->string('result')->nullable(); // clear, consider, rejected
            $table->json('result_details')->nullable(); // Detailed results from provider
            $table->decimal('confidence_score', 5, 4)->nullable(); // 0.0000 to 1.0000
            $table->json('sub_results')->nullable(); // Individual check results

            // Extracted Data (encrypted at rest)
            $table->text('extracted_first_name')->nullable();
            $table->text('extracted_last_name')->nullable();
            $table->text('extracted_date_of_birth')->nullable();
            $table->text('extracted_document_number')->nullable();
            $table->date('extracted_expiry_date')->nullable();
            $table->string('extracted_nationality', 3)->nullable(); // ISO country code
            $table->string('extracted_gender', 10)->nullable();
            $table->text('extracted_address')->nullable();

            // Face Match Data
            $table->boolean('face_match_performed')->default(false);
            $table->decimal('face_match_score', 5, 4)->nullable(); // 0.0000 to 1.0000
            $table->string('face_match_result')->nullable(); // match, no_match, unable_to_process

            // Jurisdiction & Compliance
            $table->string('jurisdiction_country', 2)->nullable(); // ISO country code
            $table->json('compliance_flags')->nullable(); // PEP, sanctions, etc.
            $table->json('aml_check_results')->nullable(); // Anti-money laundering results

            // Review Information
            $table->foreignId('reviewed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('reviewed_at')->nullable();
            $table->text('review_notes')->nullable();
            $table->string('rejection_reason')->nullable();
            $table->json('rejection_details')->nullable();

            // Retry & Expiry Management
            $table->integer('attempt_count')->default(1);
            $table->integer('max_attempts')->default(3);
            $table->timestamp('last_attempt_at')->nullable();
            $table->timestamp('expires_at')->nullable(); // Verification expiry date
            $table->timestamp('reminder_sent_at')->nullable();

            // Session Management
            $table->string('sdk_token')->nullable(); // One-time token for SDK
            $table->timestamp('sdk_token_expires_at')->nullable();
            $table->string('session_url')->nullable(); // URL for web-based verification

            // Metadata
            $table->string('ip_address', 45)->nullable();
            $table->string('user_agent')->nullable();
            $table->json('device_info')->nullable();
            $table->json('metadata')->nullable();

            $table->timestamps();
            $table->softDeletes();

            // Indexes
            $table->index('user_id');
            $table->index('status');
            $table->index('provider');
            $table->index('provider_applicant_id');
            $table->index('provider_check_id');
            $table->index('verification_level');
            $table->index('expires_at');
            $table->index('created_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('identity_verifications');
    }
};
