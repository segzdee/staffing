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
        Schema::create('holiday_calendars', function (Blueprint $table) {
            $table->id();
            $table->foreignId('business_id')->nullable()->constrained('users')->onDelete('cascade');
            $table->string('name');
            $table->string('country_code', 2);
            $table->json('included_holidays')->nullable(); // holiday IDs to include
            $table->json('excluded_holidays')->nullable(); // holiday IDs to exclude
            $table->json('custom_dates')->nullable(); // [{date, name, surge_multiplier}]
            $table->boolean('is_default')->default(false);
            $table->timestamps();

            $table->index(['business_id', 'country_code']);
            $table->index('country_code');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('holiday_calendars');
    }
};
