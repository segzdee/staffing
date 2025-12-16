<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Migration to add account lockout fields to users table.
 *
 * This supports the security feature that locks user accounts after
 * multiple failed login attempts to prevent brute-force attacks.
 *
 * Fields added:
 * - locked_until: Timestamp when the account lockout expires (null = not locked)
 * - lock_reason: Human-readable reason for the lockout
 * - failed_login_attempts: Counter for consecutive failed login attempts
 * - last_failed_login_at: Timestamp of the most recent failed login attempt
 * - locked_at: Timestamp when the account was locked
 * - locked_by_admin_id: ID of admin who manually locked account (if applicable)
 */
return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            // Account lockout timestamp - when the lock expires
            $table->timestamp('locked_until')->nullable()->after('status');

            // Reason for the lockout (for display to user and audit)
            $table->string('lock_reason')->nullable()->after('locked_until');

            // Counter for failed login attempts (resets on successful login)
            $table->unsignedTinyInteger('failed_login_attempts')->default(0)->after('lock_reason');

            // Timestamp of last failed login attempt
            $table->timestamp('last_failed_login_at')->nullable()->after('failed_login_attempts');

            // Timestamp when account was locked
            $table->timestamp('locked_at')->nullable()->after('last_failed_login_at');

            // Admin who manually locked the account (null if auto-locked)
            $table->unsignedBigInteger('locked_by_admin_id')->nullable()->after('locked_at');

            // Index for efficient queries on locked accounts
            $table->index('locked_until', 'users_locked_until_index');
            $table->index('failed_login_attempts', 'users_failed_login_attempts_index');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            // Drop indexes first
            $table->dropIndex('users_locked_until_index');
            $table->dropIndex('users_failed_login_attempts_index');

            // Drop columns
            $table->dropColumn([
                'locked_until',
                'lock_reason',
                'failed_login_attempts',
                'last_failed_login_at',
                'locked_at',
                'locked_by_admin_id',
            ]);
        });
    }
};
