<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * AGY-REG-001: Agency Registration & Onboarding System
 *
 * Creates the agency_documents table for managing document uploads
 * and verification during the agency registration process.
 */
return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('agency_documents', function (Blueprint $table) {
            $table->id();
            $table->foreignId('application_id')->constrained('agency_applications')->cascadeOnDelete();

            // Document type categorization
            $table->enum('document_type', [
                'business_license',      // Official business operating license
                'insurance_cert',        // Liability/professional insurance certificate
                'tax_id',                // Tax identification document
                'company_registration',  // Company registration/incorporation docs
                'references',            // Business/client references
                'bank_statement',        // Financial verification
                'proof_of_address',      // Office/business address verification
                'director_id',           // Director/owner identification
                'vat_certificate',       // VAT/GST registration (if applicable)
                'industry_certification', // Industry-specific certifications
                'other',                 // Other supporting documents
            ]);

            // File storage information
            $table->string('file_path');
            $table->string('file_name');
            $table->string('original_name');
            $table->string('mime_type', 100)->nullable();
            $table->unsignedBigInteger('file_size')->nullable(); // in bytes
            $table->string('disk', 50)->default('s3'); // Storage disk (s3, cloudinary, local)

            // Document metadata
            $table->string('document_number')->nullable(); // License number, certificate number, etc.
            $table->date('issue_date')->nullable();
            $table->date('expiry_date')->nullable();
            $table->string('issuing_authority')->nullable();
            $table->string('issuing_country', 2)->nullable(); // ISO 3166-1 alpha-2

            // Verification workflow
            $table->enum('verification_status', [
                'pending',    // Awaiting review
                'verified',   // Document verified and approved
                'rejected',   // Document rejected
                'expired',    // Document has expired
                'superseded', // Replaced by newer document
            ])->default('pending');

            // Verification details
            $table->foreignId('verified_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('verified_at')->nullable();
            $table->text('rejection_reason')->nullable();
            $table->text('verification_notes')->nullable();

            // Automated verification tracking (OCR, third-party API, etc.)
            $table->boolean('auto_verified')->default(false);
            $table->string('auto_verification_provider')->nullable();
            $table->json('auto_verification_result')->nullable();
            $table->decimal('auto_verification_confidence', 5, 2)->nullable(); // 0-100%

            // Version control for document updates
            $table->unsignedInteger('version')->default(1);
            $table->foreignId('replaces_document_id')->nullable()->constrained('agency_documents')->nullOnDelete();

            // Hash for duplicate detection
            $table->string('file_hash', 64)->nullable();

            $table->timestamps();
            $table->softDeletes();

            // Indexes for efficient querying
            $table->index('application_id');
            $table->index('document_type');
            $table->index('verification_status');
            $table->index('expiry_date');
            $table->index(['application_id', 'document_type']);
            $table->index(['application_id', 'verification_status']);
            $table->index('file_hash');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('agency_documents');
    }
};
