<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * SL-012: Multi-Position Shifts
 *
 * Creates the shift_positions table to support events with multiple distinct roles.
 * Each shift can have multiple positions (e.g., Bartender, Server, Security) with
 * different rates, skill requirements, and worker allocations.
 */
return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('shift_positions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('shift_id')->constrained()->onDelete('cascade');
            $table->string('title'); // e.g., "Bartender", "Server", "Security"
            $table->text('description')->nullable();
            $table->decimal('hourly_rate', 10, 2); // Position-specific rate
            $table->integer('required_workers')->default(1);
            $table->integer('filled_workers')->default(0);
            $table->json('required_skills')->nullable(); // Skill IDs required for this position
            $table->json('required_certifications')->nullable(); // Certification type IDs required
            $table->integer('minimum_experience_hours')->default(0); // Minimum hours of experience needed
            $table->enum('status', ['open', 'partially_filled', 'filled', 'cancelled'])->default('open');
            $table->timestamps();

            // Indexes for common queries
            $table->index(['shift_id', 'status']);
            $table->index('status');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('shift_positions');
    }
};
