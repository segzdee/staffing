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
        Schema::table('time_tracking_records', function (Blueprint $table) {
            // Make location_data nullable - not always required (e.g., break toggles)
            $table->json('location_data')->nullable()->change();

            // Make device_info nullable - not always provided
            $table->json('device_info')->nullable()->change();

            // Make timezone nullable with default
            $table->string('timezone', 50)->nullable()->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('time_tracking_records', function (Blueprint $table) {
            // Note: Reverting to non-nullable requires data to exist
            // This may fail if there are NULL values
            $table->json('location_data')->nullable(false)->change();
            $table->json('device_info')->nullable(false)->change();
            $table->string('timezone', 50)->nullable(false)->change();
        });
    }
};
