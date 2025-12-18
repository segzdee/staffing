<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * GLO-010: Data Residency System
 *
 * Creates the data_regions table for defining geographic data storage regions
 * with compliance framework mappings.
 */
return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('data_regions', function (Blueprint $table) {
            $table->id();
            $table->string('code', 10)->unique(); // eu, us, apac, uk
            $table->string('name');
            $table->json('countries'); // Array of country codes
            $table->string('primary_storage'); // s3-eu, s3-us, etc.
            $table->string('backup_storage')->nullable();
            $table->json('compliance_frameworks'); // GDPR, CCPA, etc.
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->index('code');
            $table->index('is_active');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('data_regions');
    }
};
