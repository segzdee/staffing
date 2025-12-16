<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Migration: Add TOTP-based Two-Factor Authentication columns to users table
 *
 * This migration adds the necessary columns for implementing TOTP (Time-based One-Time Password)
 * two-factor authentication with recovery codes.
 *
 * Columns added:
 * - two_factor_secret: Encrypted TOTP secret key for authenticator apps
 * - two_factor_recovery_codes: JSON array of encrypted recovery codes
 * - two_factor_confirmed_at: Timestamp when 2FA was confirmed/enabled
 *
 * @see App\Http\Controllers\Auth\TwoFactorAuthController
 */
return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            // TOTP secret key (encrypted) - used by authenticator apps like Google Authenticator
            $table->text('two_factor_secret')->nullable()->after('password');

            // Recovery codes (JSON array, encrypted) - 8 one-time use codes for account recovery
            $table->text('two_factor_recovery_codes')->nullable()->after('two_factor_secret');

            // Timestamp when 2FA was confirmed - null means 2FA is not enabled
            $table->timestamp('two_factor_confirmed_at')->nullable()->after('two_factor_recovery_codes');

            // Index for faster lookups when checking if 2FA is enabled
            $table->index('two_factor_confirmed_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropIndex(['two_factor_confirmed_at']);
            $table->dropColumn([
                'two_factor_secret',
                'two_factor_recovery_codes',
                'two_factor_confirmed_at',
            ]);
        });
    }
};
