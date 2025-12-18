<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * WKR-001: KYC Verifications Table
 *
 * Stores comprehensive KYC (Know Your Customer) verification records for users.
 * Supports multiple identity document types, provider integrations, and admin review workflow.
 */
return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('kyc_verifications', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');

            // Verification status
            $table->enum('status', [
                'pending',
                'in_review',
                'approved',
                'rejected',
                'expired',
            ])->default('pending')->index();

            // Document information
            $table->enum('document_type', [
                'passport',
                'drivers_license',
                'national_id',
                'residence_permit',
            ]);
            $table->string('document_number')->nullable();
            $table->string('document_country', 2)->comment('ISO 3166-1 alpha-2 country code');
            $table->date('document_expiry')->nullable();

            // Document storage paths (secure cloud storage)
            $table->string('document_front_path');
            $table->string('document_back_path')->nullable();
            $table->string('selfie_path')->nullable();

            // Verification results from provider
            $table->json('verification_result')->nullable()->comment('Provider response data');
            $table->decimal('confidence_score', 5, 4)->nullable()->comment('0.0000-1.0000 confidence');

            // Provider information
            $table->string('provider')->default('manual')->comment('manual, onfido, jumio, veriff');
            $table->string('provider_reference')->nullable()->comment('External provider reference ID');
            $table->string('provider_applicant_id')->nullable()->comment('Provider applicant/user ID');
            $table->string('provider_check_id')->nullable()->comment('Provider check/verification ID');

            // Rejection handling
            $table->text('rejection_reason')->nullable();
            $table->json('rejection_codes')->nullable()->comment('Structured rejection reasons');

            // Admin review
            $table->foreignId('reviewed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('reviewed_at')->nullable();
            $table->text('reviewer_notes')->nullable();

            // Expiration
            $table->timestamp('expires_at')->nullable()->comment('When this verification expires');

            // Retry tracking
            $table->unsignedTinyInteger('attempt_count')->default(1);
            $table->unsignedTinyInteger('max_attempts')->default(3);
            $table->timestamp('last_attempt_at')->nullable();

            // Metadata
            $table->string('ip_address', 45)->nullable();
            $table->string('user_agent')->nullable();
            $table->json('metadata')->nullable()->comment('Additional verification data');

            $table->timestamps();

            // Indexes for common queries
            $table->index(['user_id', 'status']);
            $table->index(['status', 'created_at']);
            $table->index('document_expiry');
            $table->index('expires_at');
            $table->index('provider_reference');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('kyc_verifications');
    }
};
