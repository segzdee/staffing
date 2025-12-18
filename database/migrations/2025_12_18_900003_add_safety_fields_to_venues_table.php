<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * SAF-004: Add Safety Fields to Venues Table
 *
 * Adds safety-related tracking fields to the venues table
 * for monitoring and displaying venue safety status.
 */
return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('venues', function (Blueprint $table) {
            $table->decimal('safety_score', 3, 2)->nullable()->after('average_rating');
            $table->integer('safety_ratings_count')->default(0)->after('safety_score');
            $table->integer('active_safety_flags')->default(0)->after('safety_ratings_count');
            $table->boolean('safety_verified')->default(false)->after('active_safety_flags');
            $table->timestamp('last_safety_audit')->nullable()->after('safety_verified');
            $table->enum('safety_status', [
                'good',
                'caution',
                'warning',
                'restricted',
            ])->default('good')->after('last_safety_audit');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('venues', function (Blueprint $table) {
            $table->dropColumn([
                'safety_score',
                'safety_ratings_count',
                'active_safety_flags',
                'safety_verified',
                'last_safety_audit',
                'safety_status',
            ]);
        });
    }
};
