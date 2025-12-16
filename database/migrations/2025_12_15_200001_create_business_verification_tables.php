<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     * BIZ-REG-004: Business Verification (KYB) Tables
     */
    public function up(): void
    {
        // Verification Requirements Configuration Table
        Schema::create('verification_requirements', function (Blueprint $table) {
            $table->id();
            $table->string('jurisdiction', 10); // US, UK, EU, AU, UAE, SG, etc.
            $table->string('requirement_type', 50); // kyb, insurance
            $table->string('document_type', 100); // ein, vat_certificate, business_registration, etc.
            $table->string('document_name');
            $table->text('description')->nullable();
            $table->boolean('is_required')->default(true);
            $table->json('business_types')->nullable(); // Which business types need this
            $table->json('industries')->nullable(); // Which industries need this
            $table->string('validation_api')->nullable(); // API to use for auto-validation
            $table->json('validation_rules')->nullable(); // Regex patterns, format rules
            $table->integer('validity_months')->nullable(); // How long document is valid
            $table->integer('sort_order')->default(0);
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->index(['jurisdiction', 'requirement_type', 'is_active'], 'vr_jurisdiction_type_active_idx');
            $table->index(['document_type'], 'vr_document_type_idx');
        });

        // Business Verifications Table
        Schema::create('business_verifications', function (Blueprint $table) {
            $table->id();
            $table->foreignId('business_profile_id')->constrained('business_profiles')->onDelete('cascade');
            $table->foreignId('user_id')->constrained('users')->onDelete('cascade');
            $table->string('jurisdiction', 10); // US, UK, EU, AU, UAE, SG
            $table->string('status', 30)->default('pending'); // pending, in_review, documents_required, approved, rejected, expired
            $table->string('verification_type', 30)->default('kyb'); // kyb, revalidation

            // Extracted/Validated Business Data
            $table->string('legal_business_name')->nullable();
            $table->string('trading_name')->nullable();
            $table->string('registration_number')->nullable(); // EIN, Company Number, ABN, etc.
            $table->string('tax_id')->nullable(); // VAT, GST, TRN, etc.
            $table->string('business_type')->nullable(); // LLC, Corporation, Sole Prop, etc.
            $table->string('incorporation_state')->nullable();
            $table->string('incorporation_country')->nullable();
            $table->date('incorporation_date')->nullable();
            $table->string('registered_address')->nullable();
            $table->string('registered_city')->nullable();
            $table->string('registered_state')->nullable();
            $table->string('registered_postal_code')->nullable();
            $table->string('registered_country')->nullable();

            // Verification Workflow
            $table->timestamp('submitted_at')->nullable();
            $table->timestamp('review_started_at')->nullable();
            $table->foreignId('reviewer_id')->nullable()->constrained('users');
            $table->timestamp('reviewed_at')->nullable();
            $table->text('review_notes')->nullable();
            $table->string('rejection_reason')->nullable();
            $table->json('rejection_details')->nullable();

            // Auto-Verification Results
            $table->json('auto_verification_results')->nullable(); // Results from external APIs
            $table->boolean('auto_verified')->default(false);
            $table->timestamp('auto_verified_at')->nullable();

            // Manual Review Queue
            $table->boolean('requires_manual_review')->default(false);
            $table->string('manual_review_reason')->nullable();
            $table->integer('review_priority')->default(0); // Higher = more urgent

            // Expiry Tracking
            $table->date('valid_until')->nullable();
            $table->boolean('expiry_notified')->default(false);

            // Attempt Tracking
            $table->integer('submission_attempts')->default(0);
            $table->timestamp('last_attempt_at')->nullable();

            $table->timestamps();
            $table->softDeletes();

            $table->index(['business_profile_id', 'status'], 'bv_bp_status_idx');
            $table->index(['status', 'requires_manual_review'], 'bv_status_review_idx');
            $table->index(['jurisdiction', 'status'], 'bv_jurisdiction_status_idx');
            $table->index(['reviewer_id', 'status'], 'bv_reviewer_status_idx');
            $table->index('valid_until', 'bv_valid_until_idx');
        });

        // Business Documents Table (Encrypted Storage)
        Schema::create('business_documents', function (Blueprint $table) {
            $table->id();
            $table->foreignId('business_verification_id')->constrained('business_verifications')->onDelete('cascade');
            $table->foreignId('business_profile_id')->constrained('business_profiles')->onDelete('cascade');
            $table->foreignId('requirement_id')->nullable()->constrained('verification_requirements');

            // Document Identification
            $table->string('document_type', 100); // ein_letter, business_license, vat_certificate, etc.
            $table->string('document_name'); // User-provided or auto-generated name

            // Encrypted Storage
            $table->text('file_path_encrypted'); // Encrypted S3/storage path
            $table->string('file_hash', 64); // SHA-256 hash for integrity
            $table->string('original_filename')->nullable();
            $table->string('mime_type', 100)->nullable();
            $table->unsignedBigInteger('file_size')->nullable(); // in bytes
            $table->string('storage_provider', 30)->default('s3'); // s3, cloudinary, backblaze

            // Document Status
            $table->string('status', 30)->default('pending'); // pending, processing, verified, rejected, expired

            // OCR/Extraction Results
            $table->json('extracted_data')->nullable(); // Data extracted via OCR
            $table->float('ocr_confidence', 5, 2)->nullable(); // Confidence score 0-100
            $table->timestamp('extracted_at')->nullable();

            // Validation Results
            $table->boolean('data_validated')->default(false);
            $table->json('validation_results')->nullable();
            $table->timestamp('validated_at')->nullable();

            // Manual Review
            $table->foreignId('reviewed_by')->nullable()->constrained('users');
            $table->timestamp('reviewed_at')->nullable();
            $table->text('review_notes')->nullable();
            $table->string('rejection_reason')->nullable();

            // Expiry
            $table->date('document_date')->nullable(); // Date on document
            $table->date('expiry_date')->nullable();
            $table->boolean('expiry_notified')->default(false);

            // Access Control
            $table->string('access_token', 64)->unique(); // For generating secure URLs
            $table->timestamp('access_token_expires_at')->nullable();
            $table->integer('download_count')->default(0);
            $table->timestamp('last_accessed_at')->nullable();

            $table->timestamps();
            $table->softDeletes();

            $table->index(['business_verification_id', 'document_type'], 'bd_verification_type_idx');
            $table->index(['business_profile_id', 'status'], 'bd_bp_status_idx');
            $table->index(['status', 'expiry_date'], 'bd_status_expiry_idx');
            $table->index('access_token', 'bd_access_token_idx');
        });

        // Document Access Log (Audit Trail)
        Schema::create('business_document_access_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('business_document_id')->constrained('business_documents')->onDelete('cascade');
            $table->foreignId('user_id')->nullable()->constrained('users');
            $table->string('action', 50); // view, download, upload, delete, verify, reject
            $table->string('ip_address', 45)->nullable();
            $table->string('user_agent')->nullable();
            $table->json('metadata')->nullable();
            $table->timestamp('created_at');

            $table->index(['business_document_id', 'created_at'], 'bdal_doc_created_idx');
            $table->index(['user_id', 'action'], 'bdal_user_action_idx');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('business_document_access_logs');
        Schema::dropIfExists('business_documents');
        Schema::dropIfExists('business_verifications');
        Schema::dropIfExists('verification_requirements');
    }
};
