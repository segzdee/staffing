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
        Schema::create('public_holidays', function (Blueprint $table) {
            $table->id();
            $table->string('country_code', 2);
            $table->string('region_code')->nullable(); // state/province
            $table->string('name');
            $table->string('local_name')->nullable();
            $table->date('date');
            $table->boolean('is_national')->default(true);
            $table->boolean('is_observed')->default(true); // actual day off
            $table->enum('type', ['public', 'bank', 'religious', 'cultural', 'observance'])->default('public');
            $table->decimal('surge_multiplier', 3, 2)->default(1.50);
            $table->boolean('shifts_restricted')->default(false);
            $table->timestamps();

            $table->unique(['country_code', 'region_code', 'date', 'name'], 'public_holidays_unique');
            $table->index(['country_code', 'date']);
            $table->index('date');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('public_holidays');
    }
};
