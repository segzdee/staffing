<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     * Adds filled_at timestamp to track when a shift becomes fully staffed.
     */
    public function up(): void
    {
        Schema::table('shifts', function (Blueprint $table) {
            $table->timestamp('filled_at')->nullable()->after('status');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('shifts', function (Blueprint $table) {
            $table->dropColumn('filled_at');
        });
    }
};
