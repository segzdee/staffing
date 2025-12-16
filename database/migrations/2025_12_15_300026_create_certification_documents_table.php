<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * STAFF-REG-007: Certification Documents Table
 *
 * Creates a dedicated table for storing certification document metadata
 * with encryption support for secure document storage.
 */
return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('certification_documents', function (Blueprint $table) {
            $table->id();

            // Relationships
            $table->foreignId('worker_certification_id')->constrained('worker_certifications')->onDelete('cascade');
            $table->foreignId('worker_id')->constrained('users')->onDelete('cascade');

            // Document information
            $table->string('document_type'); // certificate, id_card, wallet_card, renewal_proof
            $table->string('original_filename');
            $table->string('stored_filename');
            $table->string('mime_type');
            $table->integer('file_size'); // bytes
            $table->string('file_hash')->nullable(); // SHA256 hash for integrity

            // Storage location
            $table->string('storage_disk')->default('s3'); // s3, local, backblaze
            $table->string('storage_path');
            $table->string('storage_url')->nullable(); // CDN URL if applicable

            // Encryption
            $table->boolean('is_encrypted')->default(true);
            $table->string('encryption_algorithm')->default('AES-256-GCM');
            $table->string('encryption_key_id')->nullable(); // Reference to key management
            $table->text('encryption_iv')->nullable(); // Initialization vector (encrypted)

            // OCR Processing
            $table->boolean('ocr_processed')->default(false);
            $table->timestamp('ocr_processed_at')->nullable();
            $table->json('ocr_results')->nullable();
            $table->decimal('ocr_confidence', 5, 2)->nullable();

            // Document status
            $table->enum('status', ['pending', 'active', 'archived', 'deleted'])->default('pending');
            $table->boolean('is_current')->default(true); // Most recent document for this cert

            // Metadata
            $table->json('exif_data')->nullable();
            $table->json('metadata')->nullable();
            $table->timestamp('document_date')->nullable(); // Date on document if detected

            // Audit fields
            $table->foreignId('uploaded_by')->nullable()->constrained('users')->onDelete('set null');
            $table->ipAddress('uploaded_from_ip')->nullable();
            $table->string('uploaded_user_agent')->nullable();

            $table->timestamps();
            $table->softDeletes();

            // Indexes
            $table->index('worker_certification_id');
            $table->index('worker_id');
            $table->index('document_type');
            $table->index('status');
            $table->index('is_current');
            $table->index('ocr_processed');
            $table->index(['worker_id', 'document_type']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('certification_documents');
    }
};
