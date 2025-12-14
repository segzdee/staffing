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
        Schema::create('market_statistics', function (Blueprint $table) {
            $table->id();
            $table->string('region')->default('global');
            $table->integer('shifts_live')->default(0);
            $table->decimal('total_value', 12, 2)->default(0);
            $table->decimal('avg_hourly_rate', 8, 2)->default(0);
            $table->decimal('rate_change_percent', 5, 2)->default(0);
            $table->integer('filled_today')->default(0);
            $table->integer('workers_online')->default(0);
            $table->timestamp('calculated_at')->useCurrent();
            $table->timestamps();

            $table->index('region');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('market_statistics');
    }
};
