<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     * GLO-006: Localization Engine - Add locale preferences to users
     */
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            // Check if timezone column exists to add after it, otherwise add after email
            if (Schema::hasColumn('users', 'timezone')) {
                $table->string('locale', 10)->default('en')->after('timezone');
                $table->string('preferred_currency', 3)->default('EUR')->after('locale');
            } elseif (Schema::hasColumn('users', 'language')) {
                // If language column exists, add after it
                $table->string('locale', 10)->default('en')->after('language');
                $table->string('preferred_currency', 3)->default('EUR')->after('locale');
            } else {
                // Fallback: add after email
                $table->string('locale', 10)->default('en')->after('email');
                $table->string('preferred_currency', 3)->default('EUR')->after('locale');
            }

            // Indexes for quick lookups
            $table->index('locale');
            $table->index('preferred_currency');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropIndex(['locale']);
            $table->dropIndex(['preferred_currency']);
            $table->dropColumn(['locale', 'preferred_currency']);
        });
    }
};
