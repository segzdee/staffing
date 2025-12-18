<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * WKR-001: Add KYC fields to users table
 *
 * Adds KYC verification status tracking fields to support
 * tiered verification levels and verification timestamps.
 */
return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            // KYC verification status
            $table->boolean('kyc_verified')->default(false)->after('is_verified_worker');
            $table->timestamp('kyc_verified_at')->nullable()->after('kyc_verified');
            $table->enum('kyc_level', ['none', 'basic', 'enhanced', 'full'])->default('none')->after('kyc_verified_at');

            // Index for KYC queries
            $table->index(['kyc_verified', 'kyc_level']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropIndex(['kyc_verified', 'kyc_level']);
            $table->dropColumn(['kyc_verified', 'kyc_verified_at', 'kyc_level']);
        });
    }
};
