<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     * GLO-006: Localization Engine - Dynamic translations table
     */
    public function up(): void
    {
        Schema::create('translations', function (Blueprint $table) {
            $table->id();
            $table->string('locale', 10);
            $table->string('group'); // validation, messages, emails, shifts, etc.
            $table->string('key');
            $table->text('value');
            $table->timestamps();

            // Unique constraint for locale + group + key combination
            $table->unique(['locale', 'group', 'key'], 'translations_unique');

            // Indexes for efficient lookups
            $table->index(['locale', 'group'], 'translations_locale_group');
            $table->index('locale');
            $table->index('group');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('translations');
    }
};
