<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     * GLO-006: Localization Engine - Locales table for i18n support
     */
    public function up(): void
    {
        Schema::create('locales', function (Blueprint $table) {
            $table->id();
            $table->string('code', 10)->unique(); // en, es, fr, de, ar, zh
            $table->string('name'); // English, Spanish, French
            $table->string('native_name'); // English, Espanol, Francais
            $table->string('flag_emoji')->nullable();
            $table->boolean('is_rtl')->default(false);
            $table->string('date_format')->default('Y-m-d');
            $table->string('time_format')->default('H:i');
            $table->string('datetime_format')->default('Y-m-d H:i');
            $table->string('number_decimal_separator')->default('.');
            $table->string('number_thousands_separator')->default(',');
            $table->string('currency_position')->default('before'); // before, after
            $table->integer('translation_progress')->default(0); // percentage
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            // Indexes
            $table->index('is_active');
            $table->index(['is_active', 'code']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('locales');
    }
};
