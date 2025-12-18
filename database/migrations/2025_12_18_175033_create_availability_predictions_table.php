<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * WKR-013: Availability Forecasting - Predictions Table
 *
 * Stores predicted availability for workers on future dates.
 * Predictions are made based on historical patterns and various factors.
 */
return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('availability_predictions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->date('prediction_date');
            $table->decimal('morning_probability', 5, 4)->default(0); // 6am-12pm
            $table->decimal('afternoon_probability', 5, 4)->default(0); // 12pm-6pm
            $table->decimal('evening_probability', 5, 4)->default(0); // 6pm-12am
            $table->decimal('night_probability', 5, 4)->default(0); // 12am-6am
            $table->decimal('overall_probability', 5, 4)->default(0);
            $table->json('factors')->nullable(); // factors that influenced prediction
            $table->boolean('was_accurate')->nullable(); // set after the date passes
            $table->timestamps();

            $table->unique(['user_id', 'prediction_date']);
            $table->index('prediction_date');
            $table->index(['prediction_date', 'overall_probability'], 'avail_pred_date_prob_idx');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('availability_predictions');
    }
};
