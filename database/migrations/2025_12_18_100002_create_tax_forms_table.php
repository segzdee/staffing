<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     * GLO-002: Tax Jurisdiction Engine - Tax forms for compliance (W-9, W-8BEN, etc.)
     */
    public function up(): void
    {
        Schema::create('tax_forms', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->enum('form_type', [
                'w9',           // US persons
                'w8ben',        // Non-US individuals (beneficial owners)
                'w8bene',       // Non-US entities
                'p45',          // UK - leaving employment
                'p60',          // UK - end of year certificate
                'self_assessment', // Self-assessment declaration
                'tax_declaration', // Generic tax declaration
            ]);
            $table->string('tax_id')->nullable()->comment('Encrypted tax identification number');
            $table->string('legal_name');
            $table->string('business_name')->nullable()->comment('DBA or trade name if applicable');
            $table->text('address');
            $table->string('country_code', 2);
            $table->enum('entity_type', [
                'individual',
                'sole_proprietor',
                'llc',
                'corporation',
                'partnership',
                'trust',
                'estate',
            ])->default('individual');
            $table->string('document_url')->nullable()->comment('Uploaded form document');
            $table->enum('status', [
                'pending',      // Submitted, awaiting review
                'verified',     // Verified and approved
                'rejected',     // Rejected, needs resubmission
                'expired',      // Form has expired
            ])->default('pending');
            $table->text('rejection_reason')->nullable();
            $table->timestamp('submitted_at');
            $table->timestamp('verified_at')->nullable();
            $table->timestamp('expires_at')->nullable();
            $table->foreignId('verified_by')->nullable()->constrained('users')->nullOnDelete();
            $table->json('form_data')->nullable()->comment('Additional form-specific data');
            $table->timestamps();

            // Indexes
            $table->index(['user_id', 'form_type']);
            $table->index(['user_id', 'status']);
            $table->index('status');
            $table->index('expires_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('tax_forms');
    }
};
