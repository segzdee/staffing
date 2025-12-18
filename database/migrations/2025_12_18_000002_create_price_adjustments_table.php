<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * GLO-009: Regional Pricing System
 *
 * Migration for the price_adjustments table that stores temporary or permanent
 * price adjustments tied to regional pricing configurations.
 */
return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('price_adjustments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('regional_pricing_id')->constrained('regional_pricing')->onDelete('cascade');
            $table->string('adjustment_type'); // subscription, service_fee, surge, promotional, seasonal
            $table->string('name')->nullable(); // Human-readable name for the adjustment
            $table->text('description')->nullable();
            $table->decimal('multiplier', 5, 3)->default(1.000); // Price multiplier (1.000 = no change)
            $table->decimal('fixed_adjustment', 10, 2)->default(0); // Fixed amount to add/subtract
            $table->date('valid_from');
            $table->date('valid_until')->nullable(); // Null = no end date
            $table->json('conditions')->nullable(); // Additional conditions (time of day, user type, etc.)
            $table->boolean('is_active')->default(true);
            $table->unsignedBigInteger('created_by')->nullable();
            $table->timestamps();

            // Indexes for efficient queries
            $table->index('adjustment_type');
            $table->index('is_active');
            $table->index(['valid_from', 'valid_until']);
            $table->index('created_by');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('price_adjustments');
    }
};
