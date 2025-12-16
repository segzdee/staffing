<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * STAFF-REG-002: Worker Account Creation & Verification System
 *
 * Creates the verification_codes table for email/phone verification.
 */
return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('verification_codes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable()->constrained()->cascadeOnDelete();

            // Verification target
            $table->string('identifier'); // email address or phone number
            $table->enum('type', ['email', 'phone', 'password_reset', 'two_factor']);

            // The code/token
            $table->string('code', 10); // Short code for SMS (6 digits)
            $table->string('token', 64)->nullable(); // Long token for email links

            // Security
            $table->integer('attempts')->default(0);
            $table->integer('max_attempts')->default(5);
            $table->boolean('is_used')->default(false);
            $table->timestamp('used_at')->nullable();

            // Expiration
            $table->timestamp('expires_at');

            // Rate limiting tracking
            $table->string('ip_address', 45)->nullable();
            $table->text('user_agent')->nullable();

            // Metadata
            $table->string('purpose')->nullable(); // registration, password_reset, verification
            $table->json('metadata')->nullable(); // Additional data if needed

            $table->timestamps();

            // Indexes
            $table->index(['identifier', 'type']);
            $table->index(['code', 'type']);
            $table->index('token');
            $table->index('expires_at');
            $table->index(['user_id', 'type']);
            $table->index(['ip_address', 'created_at']); // For rate limiting
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('verification_codes');
    }
};
