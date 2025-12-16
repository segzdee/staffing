<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * STAFF-REG-005: Right-to-Work Documents
 *
 * Stores encrypted document information for RTW verification.
 * Documents themselves are stored encrypted in secure storage (S3/Backblaze).
 */
return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('rtw_documents', function (Blueprint $table) {
            $table->id();
            $table->foreignId('rtw_verification_id')
                ->constrained('right_to_work_verifications')
                ->onDelete('cascade');
            $table->foreignId('user_id')->constrained()->onDelete('cascade');

            // Document type based on jurisdiction
            // US: passport, passport_card, permanent_resident_card, employment_auth_doc,
            //     drivers_license, state_id, social_security_card, birth_certificate, etc.
            // UK: uk_passport, brp, share_code, eu_passport, settled_status
            // EU: eu_passport, national_id, work_permit, residence_permit
            // AU: au_passport, visa_grant_notice, immicard
            // UAE: emirates_id, work_permit, visa
            // SG: employment_pass, s_pass, work_permit, nric
            $table->string('document_type', 50);

            // Document categorization (for I-9 compliance)
            $table->string('document_list')->nullable(); // list_a, list_b, list_c (US specific)

            // Encrypted document details (sensitive data)
            $table->text('document_number_encrypted')->nullable(); // Encrypted
            $table->text('issuing_authority_encrypted')->nullable(); // Encrypted

            // Non-sensitive document metadata
            $table->string('issuing_country', 3)->nullable();
            $table->date('issue_date')->nullable();
            $table->date('expiry_date')->nullable()->index();

            // File storage (encrypted file path/reference)
            $table->text('file_path_encrypted'); // Encrypted S3/storage path
            $table->string('file_hash', 64); // SHA-256 hash for integrity verification
            $table->string('file_mime_type', 100);
            $table->unsignedInteger('file_size'); // bytes
            $table->string('encryption_key_id')->nullable(); // Reference to key used for encryption

            // Verification status
            $table->enum('status', [
                'pending',
                'verified',
                'rejected',
                'expired',
            ])->default('pending');
            $table->text('verification_notes')->nullable();
            $table->foreignId('verified_by')->nullable()->constrained('users');
            $table->timestamp('verified_at')->nullable();

            // OCR/extraction data (encrypted)
            $table->text('extracted_data_encrypted')->nullable(); // JSON with extracted document data
            $table->decimal('ocr_confidence_score', 5, 2)->nullable();

            // Audit trail
            $table->json('audit_log')->nullable();
            $table->ipAddress('upload_ip')->nullable();
            $table->string('upload_user_agent')->nullable();

            $table->timestamps();
            $table->softDeletes();

            // Indexes
            $table->index(['user_id', 'document_type'], 'rtwd_user_type_idx');
            $table->index(['rtw_verification_id', 'status'], 'rtwd_verification_status_idx');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('rtw_documents');
    }
};
