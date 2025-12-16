<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * STAFF-REG-007: Worker Certifications Enhancement
 *
 * Enhances the worker_certifications table with additional verification,
 * document tracking, and expiry management fields.
 */
return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('worker_certifications', function (Blueprint $table) {
            // Link to new certification_types table (optional - maintains backward compat)
            $table->foreignId('certification_type_id')->nullable()->after('certification_id')
                ->constrained('certification_types')->onDelete('set null');

            // Issuer information
            $table->string('issuing_authority')->nullable()->after('expiry_date');
            $table->string('issuing_state')->nullable()->after('issuing_authority');
            $table->string('issuing_country')->nullable()->after('issuing_state');

            // Additional verification tracking
            $table->string('verification_method')->nullable()->after('verification_status');
            // Method: manual, api, ocr, issuer_lookup
            $table->timestamp('verification_attempted_at')->nullable()->after('verification_method');
            $table->json('verification_response')->nullable()->after('verification_attempted_at');

            // Automated verification fields
            $table->string('extracted_cert_number')->nullable()->after('verification_response');
            $table->string('extracted_name')->nullable()->after('extracted_cert_number');
            $table->date('extracted_issue_date')->nullable()->after('extracted_name');
            $table->date('extracted_expiry_date')->nullable()->after('extracted_issue_date');
            $table->decimal('ocr_confidence_score', 5, 2)->nullable()->after('extracted_expiry_date');

            // Expiry tracking
            $table->integer('expiry_reminders_sent')->default(0)->after('expiry_reminder_sent');
            $table->timestamp('last_reminder_sent_at')->nullable()->after('expiry_reminders_sent');
            $table->boolean('renewal_in_progress')->default(false)->after('last_reminder_sent_at');
            $table->foreignId('renewal_of_certification_id')->nullable()->after('renewal_in_progress')
                ->constrained('worker_certifications')->onDelete('set null');

            // Document encryption tracking
            $table->string('document_storage_path')->nullable()->after('renewal_of_certification_id');
            $table->string('document_encryption_key_id')->nullable()->after('document_storage_path');
            $table->boolean('document_encrypted')->default(false)->after('document_encryption_key_id');

            // Status tracking
            $table->boolean('is_primary')->default(true)->after('document_encrypted');
            // For certs that worker has multiple of (e.g., from different states)

            // Metadata
            $table->json('metadata')->nullable()->after('is_primary');

            // Soft deletes
            $table->softDeletes();

            // Indexes
            $table->index('certification_type_id', 'wc_cert_type_id_idx');
            $table->index('verification_method', 'wc_verification_method_idx');
            $table->index('renewal_in_progress', 'wc_renewal_in_progress_idx');
            $table->index('is_primary', 'wc_is_primary_idx');
            $table->index(['worker_id', 'certification_type_id'], 'wc_worker_cert_type_idx');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('worker_certifications', function (Blueprint $table) {
            $table->dropIndex(['certification_type_id']);
            $table->dropIndex(['verification_method']);
            $table->dropIndex(['renewal_in_progress']);
            $table->dropIndex(['is_primary']);
            $table->dropIndex(['worker_id', 'certification_type_id']);

            $table->dropForeign(['certification_type_id']);
            $table->dropForeign(['renewal_of_certification_id']);

            $table->dropColumn([
                'certification_type_id',
                'issuing_authority',
                'issuing_state',
                'issuing_country',
                'verification_method',
                'verification_attempted_at',
                'verification_response',
                'extracted_cert_number',
                'extracted_name',
                'extracted_issue_date',
                'extracted_expiry_date',
                'ocr_confidence_score',
                'expiry_reminders_sent',
                'last_reminder_sent_at',
                'renewal_in_progress',
                'renewal_of_certification_id',
                'document_storage_path',
                'document_encryption_key_id',
                'document_encrypted',
                'is_primary',
                'metadata',
                'deleted_at',
            ]);
        });
    }
};
