<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * SAF-003: Shift Certification Requirements
 *
 * Creates a pivot table linking shifts to required safety certifications.
 * This allows businesses to specify which certifications workers must have
 * before they can be assigned to a shift.
 */
return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('shift_certification_requirements', function (Blueprint $table) {
            $table->id();
            $table->foreignId('shift_id')->constrained()->onDelete('cascade');
            $table->foreignId('safety_certification_id')->constrained()->onDelete('cascade');
            $table->boolean('is_mandatory')->default(true);
            $table->timestamps();

            // Unique constraint to prevent duplicate requirements
            $table->unique(
                ['shift_id', 'safety_certification_id'],
                'scr_shift_cert_unique'
            );

            // Indexes for performance
            $table->index('shift_id', 'scr_shift_idx');
            $table->index('safety_certification_id', 'scr_cert_idx');
            $table->index(['shift_id', 'is_mandatory'], 'scr_shift_mandatory_idx');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('shift_certification_requirements');
    }
};
