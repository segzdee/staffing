<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * SAF-003: Safety Certifications System
 *
 * Creates the safety_certifications table for managing certification types
 * that can be required for shifts or held by workers.
 */
return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('safety_certifications', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('slug')->unique();
            $table->text('description')->nullable();
            $table->enum('category', [
                'food_safety',
                'health',
                'security',
                'industry_specific',
                'general',
            ]);
            $table->string('issuing_authority')->nullable();
            $table->integer('validity_months')->nullable();
            $table->boolean('requires_renewal')->default(true);
            $table->json('applicable_industries')->nullable();
            $table->json('applicable_positions')->nullable();
            $table->boolean('is_mandatory')->default(false);
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            // Indexes for performance
            $table->index('category', 'sc_category_idx');
            $table->index('is_active', 'sc_is_active_idx');
            $table->index('is_mandatory', 'sc_is_mandatory_idx');
            $table->index(['category', 'is_active'], 'sc_category_active_idx');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('safety_certifications');
    }
};
