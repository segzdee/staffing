<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * STAFF-REG-004: Identity Verification - Document Storage Table
 *
 * Stores encrypted document information for identity verification.
 * Actual document files are stored in encrypted cloud storage (S3/Cloudinary).
 */
return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('verification_documents', function (Blueprint $table) {
            $table->id();
            $table->foreignId('identity_verification_id')
                ->constrained('identity_verifications')
                ->onDelete('cascade');
            $table->foreignId('user_id')->constrained('users')->onDelete('cascade');

            // Document Type Information
            $table->enum('document_type', [
                'passport',
                'driving_license',
                'national_id',
                'residence_permit',
                'visa',
                'tax_id',
                'utility_bill',       // For address proof
                'bank_statement',     // For address proof
                'selfie',             // Face image
                'liveness_video',     // Liveness check video
                'other'
            ]);
            $table->string('document_subtype')->nullable(); // e.g., 'front', 'back', 'full'

            // Provider Document IDs
            $table->string('provider_document_id')->nullable(); // Onfido document ID
            $table->string('provider_file_id')->nullable(); // Provider's file reference

            // Storage Information (encrypted)
            $table->string('storage_provider')->default('s3'); // s3, cloudinary, local
            $table->text('storage_path')->nullable(); // Encrypted path
            $table->text('storage_key')->nullable(); // Encryption key identifier
            $table->string('storage_bucket')->nullable();
            $table->string('storage_region')->nullable();

            // File Information
            $table->string('original_filename')->nullable();
            $table->string('mime_type')->nullable();
            $table->integer('file_size')->nullable(); // In bytes
            $table->string('file_hash')->nullable(); // SHA-256 hash for integrity
            $table->string('encryption_algorithm')->default('AES-256-GCM');
            $table->text('encryption_iv')->nullable(); // Initialization vector

            // Processing Status
            $table->enum('status', [
                'pending',      // Uploaded, awaiting processing
                'processing',   // Being analyzed
                'verified',     // Document verified
                'rejected',     // Document rejected
                'expired',      // Document expired
                'deleted'       // Marked for deletion
            ])->default('pending');

            // Verification Results
            $table->string('verification_result')->nullable(); // clear, consider, rejected
            $table->json('verification_details')->nullable(); // Detailed analysis results
            $table->decimal('authenticity_score', 5, 4)->nullable(); // 0.0000 to 1.0000
            $table->decimal('quality_score', 5, 4)->nullable(); // Image quality score

            // Extracted Document Data (encrypted)
            $table->text('extracted_data')->nullable(); // JSON, encrypted at rest
            $table->date('document_issue_date')->nullable();
            $table->date('document_expiry_date')->nullable();
            $table->string('issuing_country', 2)->nullable(); // ISO country code
            $table->string('issuing_authority')->nullable();

            // OCR Results
            $table->json('ocr_results')->nullable();
            $table->json('mrz_data')->nullable(); // Machine Readable Zone data

            // Security Checks
            $table->json('fraud_signals')->nullable(); // Tampering, forgery signals
            $table->boolean('is_authentic')->nullable();
            $table->boolean('is_expired')->default(false);
            $table->boolean('is_tampered')->default(false);

            // Retention & Compliance
            $table->timestamp('retention_expires_at')->nullable(); // When to delete for GDPR
            $table->timestamp('deletion_requested_at')->nullable();
            $table->timestamp('deleted_at_provider_at')->nullable();

            // Metadata
            $table->json('metadata')->nullable();

            $table->timestamps();
            $table->softDeletes();

            // Indexes
            $table->index('identity_verification_id');
            $table->index('user_id');
            $table->index('document_type');
            $table->index('status');
            $table->index('document_expiry_date');
            $table->index('retention_expires_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('verification_documents');
    }
};
