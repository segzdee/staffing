<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * STAFF-REG-007: Skill-Certification Requirements Pivot Table
 *
 * Links skills to their required certifications with regional requirements support.
 */
return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('skill_certification_requirements', function (Blueprint $table) {
            $table->id();

            // Relationships
            $table->foreignId('skill_id')->constrained('skills')->onDelete('cascade');
            $table->foreignId('certification_type_id')->constrained('certification_types')->onDelete('cascade');

            // Requirement level
            $table->enum('requirement_level', ['required', 'recommended', 'optional'])->default('required');

            // Regional requirements (some certs only required in certain regions)
            $table->json('required_in_countries')->nullable(); // If null, required everywhere
            $table->json('required_in_states')->nullable(); // State-level requirements

            // Experience level requirements (cert may only be required at certain levels)
            $table->json('required_at_experience_levels')->nullable(); // ['intermediate', 'advanced', 'expert']

            // Description
            $table->text('notes')->nullable();

            // Status
            $table->boolean('is_active')->default(true);

            $table->timestamps();

            // Indexes
            $table->unique(['skill_id', 'certification_type_id'], 'scr_skill_cert_unique');
            $table->index('requirement_level', 'scr_requirement_level_idx');
            $table->index('is_active', 'scr_is_active_idx');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('skill_certification_requirements');
    }
};
