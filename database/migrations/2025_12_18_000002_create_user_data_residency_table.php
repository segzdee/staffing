<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * GLO-010: Data Residency System
 *
 * Creates the user_data_residency table for tracking which data region
 * each user's data is stored in, along with consent information.
 */
return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('user_data_residency', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->foreignId('data_region_id')->constrained('data_regions');
            $table->string('detected_country', 2);
            $table->boolean('user_selected')->default(false);
            $table->timestamp('consent_given_at')->nullable();
            $table->json('data_locations')->nullable(); // Track where data is stored
            $table->timestamps();

            $table->unique('user_id');
            $table->index('data_region_id');
            $table->index('detected_country');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('user_data_residency');
    }
};
