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
        Schema::table('shifts', function (Blueprint $table) {
            // Add allow_agencies column to control if agencies can assign workers to this shift
            // Default to true to allow agencies by default (backward compatible)
            $table->boolean('allow_agencies')->default(true)->after('posted_by_agent')->index();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('shifts', function (Blueprint $table) {
            $table->dropColumn('allow_agencies');
        });
    }
};
