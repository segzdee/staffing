<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * GLO-009: Regional Pricing System
 *
 * Migration for the regional_pricing table that stores regional price configurations
 * based on location and purchasing power parity (PPP).
 */
return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('regional_pricing', function (Blueprint $table) {
            $table->id();
            $table->string('country_code', 2);
            $table->string('region_code', 10)->nullable();
            $table->string('currency_code', 3);
            $table->decimal('ppp_factor', 5, 3)->default(1.000); // Purchasing Power Parity
            $table->decimal('min_hourly_rate', 8, 2);
            $table->decimal('max_hourly_rate', 8, 2);
            $table->decimal('platform_fee_rate', 5, 2)->default(15.00); // Platform fee percentage
            $table->decimal('worker_fee_rate', 5, 2)->default(5.00); // Worker fee percentage
            $table->json('tier_adjustments')->nullable(); // Adjustments per subscription tier
            $table->string('country_name')->nullable();
            $table->string('region_name')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            // Ensure unique combination of country and region
            $table->unique(['country_code', 'region_code']);

            // Index for quick lookups
            $table->index('country_code');
            $table->index('is_active');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('regional_pricing');
    }
};
