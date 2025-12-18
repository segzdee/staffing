<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * WKR-013: Availability Forecasting - Patterns Table
 *
 * Stores learned patterns from worker historical availability data.
 * Used for ML-based availability prediction.
 */
return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('availability_patterns', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->tinyInteger('day_of_week'); // 0-6 (Sunday-Saturday)
            $table->time('typical_start_time')->nullable();
            $table->time('typical_end_time')->nullable();
            $table->decimal('availability_probability', 5, 4)->default(0); // 0.0000-1.0000
            $table->integer('historical_shifts_count')->default(0);
            $table->integer('historical_available_count')->default(0);
            $table->timestamps();

            $table->unique(['user_id', 'day_of_week']);
            $table->index('day_of_week');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('availability_patterns');
    }
};
