<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     * BIZ-REG-005: Insurance & Compliance Tables
     */
    public function up(): void
    {
        // Insurance Requirements Configuration
        Schema::create('insurance_requirements', function (Blueprint $table) {
            $table->id();
            $table->string('jurisdiction', 10); // US, UK, EU, AU, UAE, SG
            $table->string('insurance_type', 50); // general_liability, workers_comp, employers_liability, professional_liability
            $table->string('insurance_name');
            $table->text('description')->nullable();
            $table->boolean('is_required')->default(true);
            $table->boolean('is_jurisdiction_dependent')->default(false); // Some states/regions may differ
            $table->json('required_in_regions')->nullable(); // Specific states/regions where required
            $table->json('business_types')->nullable(); // Which business types need this
            $table->json('industries')->nullable(); // Which industries need this

            // Coverage Requirements
            $table->unsignedBigInteger('minimum_coverage_amount')->nullable(); // In cents
            $table->string('coverage_currency', 3)->default('USD');
            $table->unsignedBigInteger('minimum_per_occurrence')->nullable(); // In cents
            $table->unsignedBigInteger('minimum_aggregate')->nullable(); // In cents

            // Additional Requirements
            $table->boolean('additional_insured_required')->default(false);
            $table->text('additional_insured_wording')->nullable();
            $table->boolean('waiver_of_subrogation_required')->default(false);

            $table->integer('sort_order')->default(0);
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->index(['jurisdiction', 'insurance_type', 'is_active'], 'ir_jurisdiction_type_active_idx');
        });

        // Insurance Verifications Table
        Schema::create('insurance_verifications', function (Blueprint $table) {
            $table->id();
            $table->foreignId('business_profile_id')->constrained('business_profiles')->onDelete('cascade');
            $table->foreignId('user_id')->constrained('users')->onDelete('cascade');
            $table->string('jurisdiction', 10);

            // Overall Status
            $table->string('status', 30)->default('pending'); // pending, partial, compliant, non_compliant, expired
            $table->boolean('is_fully_compliant')->default(false);
            $table->timestamp('compliant_since')->nullable();
            $table->timestamp('last_compliance_check')->nullable();

            // Compliance Summary
            $table->json('compliance_summary')->nullable(); // Summary of all insurance statuses
            $table->json('missing_coverages')->nullable(); // List of required but missing coverages
            $table->json('expiring_soon')->nullable(); // Coverages expiring within 30 days

            // Suspension Status
            $table->boolean('is_suspended')->default(false);
            $table->timestamp('suspended_at')->nullable();
            $table->string('suspension_reason')->nullable();
            $table->timestamp('suspension_lifted_at')->nullable();

            // Notifications
            $table->json('notification_history')->nullable();
            $table->timestamp('last_reminder_sent_at')->nullable();
            $table->integer('reminders_sent')->default(0);

            $table->timestamps();
            $table->softDeletes();

            $table->index(['business_profile_id', 'status'], 'iv_bp_status_idx');
            $table->index(['status', 'is_fully_compliant'], 'iv_status_compliant_idx');
            $table->index('jurisdiction', 'iv_jurisdiction_idx');
        });

        // Insurance Certificates Table (Encrypted Storage)
        Schema::create('insurance_certificates', function (Blueprint $table) {
            $table->id();
            $table->foreignId('insurance_verification_id')->constrained('insurance_verifications')->onDelete('cascade');
            $table->foreignId('business_profile_id')->constrained('business_profiles')->onDelete('cascade');
            $table->foreignId('requirement_id')->nullable()->constrained('insurance_requirements');

            // Insurance Details
            $table->string('insurance_type', 50); // general_liability, workers_comp, etc.
            $table->string('policy_number')->nullable();
            $table->string('carrier_name');
            $table->string('carrier_naic_code')->nullable(); // For US carriers
            $table->string('carrier_am_best_rating')->nullable(); // A++, A+, A, etc.

            // Named Insured
            $table->string('named_insured');
            $table->text('insured_address')->nullable();

            // Coverage Details
            $table->unsignedBigInteger('coverage_amount'); // In cents
            $table->string('coverage_currency', 3)->default('USD');
            $table->unsignedBigInteger('per_occurrence_limit')->nullable();
            $table->unsignedBigInteger('aggregate_limit')->nullable();
            $table->unsignedBigInteger('deductible_amount')->nullable();
            $table->json('coverage_details')->nullable(); // Additional coverage specifics

            // Policy Period
            $table->date('effective_date');
            $table->date('expiry_date');
            $table->boolean('is_expired')->default(false);
            $table->boolean('auto_renews')->default(false);

            // Additional Insured
            $table->boolean('has_additional_insured')->default(false);
            $table->boolean('additional_insured_verified')->default(false);
            $table->text('additional_insured_text')->nullable();

            // Waiver of Subrogation
            $table->boolean('has_waiver_of_subrogation')->default(false);
            $table->boolean('waiver_verified')->default(false);

            // Certificate Storage (Encrypted)
            $table->text('file_path_encrypted')->nullable();
            $table->string('file_hash', 64)->nullable();
            $table->string('original_filename')->nullable();
            $table->string('mime_type', 100)->nullable();
            $table->unsignedBigInteger('file_size')->nullable();
            $table->string('storage_provider', 30)->default('s3');

            // Verification Status
            $table->string('status', 30)->default('pending'); // pending, verified, rejected, expired
            $table->boolean('carrier_verified')->default(false);
            $table->timestamp('carrier_verified_at')->nullable();
            $table->json('carrier_verification_response')->nullable();

            // OCR/Extraction
            $table->json('extracted_data')->nullable();
            $table->float('extraction_confidence', 5, 2)->nullable();
            $table->timestamp('extracted_at')->nullable();

            // Manual Review
            $table->foreignId('reviewed_by')->nullable()->constrained('users');
            $table->timestamp('reviewed_at')->nullable();
            $table->text('review_notes')->nullable();
            $table->string('rejection_reason')->nullable();

            // Coverage Validation
            $table->boolean('meets_minimum_coverage')->default(false);
            $table->json('coverage_validation_details')->nullable();

            // Access Control
            $table->string('access_token', 64)->unique();
            $table->timestamp('access_token_expires_at')->nullable();
            $table->integer('download_count')->default(0);
            $table->timestamp('last_accessed_at')->nullable();

            // Expiry Notifications
            $table->boolean('expiry_90_day_notified')->default(false);
            $table->boolean('expiry_60_day_notified')->default(false);
            $table->boolean('expiry_30_day_notified')->default(false);
            $table->boolean('expiry_14_day_notified')->default(false);
            $table->boolean('expiry_7_day_notified')->default(false);
            $table->boolean('expired_notified')->default(false);

            $table->timestamps();
            $table->softDeletes();

            $table->index(['insurance_verification_id', 'insurance_type'], 'ic_verification_type_idx');
            $table->index(['business_profile_id', 'status'], 'ic_bp_status_idx');
            $table->index(['status', 'expiry_date'], 'ic_status_expiry_idx');
            $table->index(['expiry_date', 'is_expired'], 'ic_expiry_expired_idx');
            $table->index('access_token', 'ic_access_token_idx');
            $table->index('carrier_name', 'ic_carrier_name_idx');
        });

        // Insurance Certificate Access Log
        Schema::create('insurance_certificate_access_logs', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('insurance_certificate_id');
            $table->foreign('insurance_certificate_id', 'ical_cert_id_fk')->references('id')->on('insurance_certificates')->onDelete('cascade');
            $table->foreignId('user_id')->nullable()->constrained('users');
            $table->string('action', 50); // view, download, upload, verify, reject
            $table->string('ip_address', 45)->nullable();
            $table->string('user_agent')->nullable();
            $table->json('metadata')->nullable();
            $table->timestamp('created_at');

            $table->index(['insurance_certificate_id', 'created_at'], 'ical_cert_created_idx');
            $table->index(['user_id', 'action'], 'ical_user_action_idx');
        });

        // Known Insurance Carriers (for validation)
        Schema::create('insurance_carriers', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('naic_code')->nullable()->unique(); // US National Association of Insurance Commissioners
            $table->string('am_best_rating')->nullable();
            $table->string('am_best_financial_size')->nullable();
            $table->string('country', 2)->default('US');
            $table->json('operating_regions')->nullable(); // States/countries where licensed
            $table->string('verification_api_endpoint')->nullable();
            $table->string('verification_api_type')->nullable(); // rest, soap, manual
            $table->boolean('supports_coi_verification')->default(false);
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->index(['name', 'is_active']);
            $table->index('naic_code');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('insurance_carriers');
        Schema::dropIfExists('insurance_certificate_access_logs');
        Schema::dropIfExists('insurance_certificates');
        Schema::dropIfExists('insurance_verifications');
        Schema::dropIfExists('insurance_requirements');
    }
};
