<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * STAFF-REG-006: Background Check Consents
 *
 * Stores consent information for background checks (FCRA compliance).
 * Captures electronic signatures and consent details.
 */
return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('background_check_consents', function (Blueprint $table) {
            $table->id();
            $table->foreignId('background_check_id')
                ->constrained()
                ->onDelete('cascade');
            $table->foreignId('user_id')->constrained()->onDelete('cascade');

            // Consent type
            $table->enum('consent_type', [
                'fcra_disclosure',      // US FCRA disclosure
                'fcra_authorization',   // US FCRA authorization
                'dbs_consent',          // UK DBS consent
                'general_consent',      // General background check consent
                'data_processing',      // GDPR data processing consent
            ]);

            // Consent status
            $table->boolean('consented')->default(false);
            $table->timestamp('consented_at')->nullable();

            // Electronic signature
            $table->text('signature_data_encrypted')->nullable(); // Base64 signature or typed name
            $table->enum('signature_type', [
                'typed',
                'drawn',
                'checkbox',
            ])->nullable();
            $table->string('signatory_name')->nullable();

            // Document version tracking
            $table->string('document_version', 20)->nullable(); // Version of consent document shown
            $table->string('document_hash', 64)->nullable(); // Hash of consent document

            // Collection metadata
            $table->ipAddress('consent_ip')->nullable();
            $table->string('consent_user_agent')->nullable();
            $table->string('consent_device_fingerprint')->nullable();
            $table->string('consent_location')->nullable(); // Geolocation if available

            // Legal requirements
            $table->text('full_disclosure_text')->nullable(); // The actual text shown
            $table->boolean('separate_document_provided')->default(false);

            // Withdrawal
            $table->boolean('is_withdrawn')->default(false);
            $table->timestamp('withdrawn_at')->nullable();
            $table->text('withdrawal_reason')->nullable();

            // Audit trail
            $table->json('audit_log')->nullable();

            $table->timestamps();

            // Indexes
            $table->index(['user_id', 'consent_type']);
            $table->index(['background_check_id', 'consented']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('background_check_consents');
    }
};
