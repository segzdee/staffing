<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('demand_metrics', function (Blueprint $table) {
            $table->id();
            $table->string('region')->nullable();
            $table->string('skill_category')->nullable();
            $table->date('metric_date');
            $table->integer('shifts_posted')->default(0);
            $table->integer('shifts_filled')->default(0);
            $table->integer('workers_available')->default(0);
            $table->decimal('fill_rate', 5, 2)->default(0);
            $table->decimal('supply_demand_ratio', 5, 2)->default(1);
            $table->decimal('calculated_surge', 3, 2)->default(1);
            $table->timestamps();

            $table->unique(['region', 'skill_category', 'metric_date'], 'demand_metrics_unique');
            $table->index(['metric_date', 'region']);
            $table->index(['skill_category', 'metric_date']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('demand_metrics');
    }
};
