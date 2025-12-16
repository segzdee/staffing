<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * STAFF-REG-007: Certification Types Master Table
 *
 * Creates a comprehensive certification types table for tracking all available
 * certifications across industries.
 */
return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('certification_types', function (Blueprint $table) {
            $table->id();

            // Basic information
            $table->string('name');
            $table->string('short_name')->nullable(); // e.g., TIPS, RSA, RBS, CPR
            $table->string('slug')->unique();
            $table->text('description')->nullable();

            // Categorization
            $table->string('industry'); // hospitality, warehousing, healthcare, retail, events, administrative
            $table->string('category')->nullable(); // food_safety, alcohol, equipment, medical, security

            // Issuing organization
            $table->string('issuing_organization')->nullable();
            $table->string('issuing_organization_url')->nullable();
            $table->json('recognized_issuers')->nullable(); // Array of accepted issuers

            // Validity and expiration
            $table->boolean('has_expiration')->default(true);
            $table->integer('default_validity_months')->nullable(); // Default validity period
            $table->integer('renewal_reminder_days')->default(60); // Days before expiry to remind

            // Verification settings
            $table->boolean('auto_verifiable')->default(false); // Can be verified via API
            $table->string('verification_api_provider')->nullable(); // e.g., 'checr', 'certify_me', etc.
            $table->json('verification_config')->nullable(); // API configuration

            // Requirements
            $table->boolean('requires_document_upload')->default(true);
            $table->json('required_document_types')->nullable(); // ['certificate', 'id_card', 'wallet_card']
            $table->text('renewal_instructions')->nullable();

            // Regional availability
            $table->json('available_countries')->nullable(); // ['US', 'CA', 'GB']
            $table->json('available_states')->nullable(); // State-specific certs like RSA by state

            // Display settings
            $table->string('icon')->nullable();
            $table->string('color')->nullable();
            $table->integer('sort_order')->default(0);
            $table->boolean('is_active')->default(true);

            // Metadata
            $table->json('metadata')->nullable();

            $table->timestamps();
            $table->softDeletes();

            // Indexes
            $table->index('industry', 'ct_industry_idx');
            $table->index('category', 'ct_category_idx');
            $table->index('is_active', 'ct_is_active_idx');
            $table->index('auto_verifiable', 'ct_auto_verifiable_idx');
            $table->index(['industry', 'category'], 'ct_industry_category_idx');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('certification_types');
    }
};
