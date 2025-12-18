<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * WKR-013: Availability Forecasting - Demand Forecasts Table
 *
 * Stores predicted demand (worker needs) for future dates.
 * Includes supply predictions and gap analysis.
 */
return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('demand_forecasts', function (Blueprint $table) {
            $table->id();
            $table->date('forecast_date');
            $table->foreignId('venue_id')->nullable()->constrained()->onDelete('cascade');
            $table->string('skill_category')->nullable();
            $table->string('region')->nullable();
            $table->integer('predicted_demand')->default(0); // number of workers needed
            $table->integer('predicted_supply')->default(0); // number likely available
            $table->decimal('supply_demand_ratio', 5, 2)->default(0);
            $table->enum('demand_level', ['low', 'normal', 'high', 'critical'])->default('normal');
            $table->json('factors')->nullable();
            $table->timestamps();

            $table->index(['forecast_date', 'region']);
            $table->index(['forecast_date', 'skill_category']);
            $table->index('demand_level');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('demand_forecasts');
    }
};
