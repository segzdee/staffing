<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * FIN-001: Volume Discount Tiers
 *
 * Creates the volume_discount_tiers table that stores configurable
 * discount tiers based on monthly shift volume. Businesses automatically
 * qualify for lower platform fees based on their usage.
 */
return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('volume_discount_tiers', function (Blueprint $table) {
            $table->id();
            $table->string('name'); // Starter, Growth, Scale, Enterprise
            $table->string('slug')->unique(); // starter, growth, scale, enterprise
            $table->integer('min_shifts_monthly')->default(0);
            $table->integer('max_shifts_monthly')->nullable(); // null = unlimited
            $table->decimal('platform_fee_percent', 5, 2); // e.g., 35.00, 30.00, 25.00, 20.00
            $table->decimal('min_monthly_spend', 10, 2)->nullable(); // Optional minimum spend requirement
            $table->decimal('max_monthly_spend', 10, 2)->nullable(); // Optional maximum spend cap
            $table->json('benefits')->nullable(); // Additional tier benefits (JSON array)
            $table->string('badge_color')->nullable(); // Color for UI display
            $table->string('badge_icon')->nullable(); // Icon for UI display
            $table->text('description')->nullable(); // Marketing description
            $table->boolean('is_active')->default(true);
            $table->boolean('is_default')->default(false); // Default tier for new businesses
            $table->integer('sort_order')->default(0);
            $table->timestamps();

            // Indexes
            $table->index('is_active');
            $table->index('sort_order');
            $table->index(['min_shifts_monthly', 'max_shifts_monthly'], 'vol_disc_tiers_shifts_idx');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('volume_discount_tiers');
    }
};
