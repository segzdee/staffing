<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     * BIZ-REG-003: Business Contacts for primary, billing, and operations
     */
    public function up(): void
    {
        Schema::create('business_contacts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('business_profile_id')->constrained('business_profiles')->onDelete('cascade');

            // Contact type
            $table->enum('contact_type', ['primary', 'billing', 'operations', 'emergency', 'hr'])->default('primary');

            // Contact details
            $table->string('first_name');
            $table->string('last_name');
            $table->string('job_title')->nullable();
            $table->string('email');
            $table->string('phone')->nullable();
            $table->string('phone_extension')->nullable();
            $table->string('mobile')->nullable();

            // Communication preferences
            $table->boolean('receives_shift_notifications')->default(true);
            $table->boolean('receives_billing_notifications')->default(false);
            $table->boolean('receives_marketing_emails')->default(false);
            $table->string('preferred_contact_method')->default('email'); // email, phone, mobile

            // Verification
            $table->boolean('email_verified')->default(false);
            $table->timestamp('email_verified_at')->nullable();
            $table->string('verification_token')->nullable();

            // Status
            $table->boolean('is_active')->default(true);
            $table->boolean('is_primary')->default(false);

            $table->timestamps();
            $table->softDeletes();

            // Indexes
            $table->index('business_profile_id');
            $table->index('contact_type');
            $table->index('email');
            $table->index('is_primary');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('business_contacts');
    }
};
