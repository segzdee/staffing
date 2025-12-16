<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     * BIZ-REG-003: Business Addresses for registered, billing, and operating locations
     */
    public function up(): void
    {
        Schema::create('business_addresses', function (Blueprint $table) {
            $table->id();
            $table->foreignId('business_profile_id')->constrained('business_profiles')->onDelete('cascade');

            // Address type
            $table->enum('address_type', ['registered', 'billing', 'operating', 'mailing', 'headquarters'])->default('operating');

            // Address label
            $table->string('label')->nullable(); // e.g., "Main Office", "Warehouse A"

            // Address details
            $table->string('address_line_1');
            $table->string('address_line_2')->nullable();
            $table->string('city');
            $table->string('state_province');
            $table->string('postal_code');
            $table->string('country_code', 2); // ISO 3166-1 alpha-2
            $table->string('country_name');

            // Geolocation (for operating addresses)
            $table->decimal('latitude', 10, 8)->nullable();
            $table->decimal('longitude', 11, 8)->nullable();
            $table->string('timezone')->nullable();

            // Jurisdiction info (for tax/legal purposes)
            $table->string('jurisdiction_code')->nullable();
            $table->string('tax_region')->nullable();

            // Contact at this location
            $table->string('contact_name')->nullable();
            $table->string('contact_phone')->nullable();

            // Status
            $table->boolean('is_primary')->default(false);
            $table->boolean('is_active')->default(true);
            $table->boolean('is_verified')->default(false);
            $table->timestamp('verified_at')->nullable();

            $table->timestamps();
            $table->softDeletes();

            // Indexes
            $table->index('business_profile_id');
            $table->index('address_type');
            $table->index(['latitude', 'longitude']);
            $table->index('country_code');
            $table->index('is_primary');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('business_addresses');
    }
};
