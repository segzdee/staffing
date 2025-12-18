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
        Schema::create('device_fingerprints', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->string('fingerprint_hash');
            $table->json('fingerprint_data')->nullable(); // browser, os, screen, etc.
            $table->string('ip_address')->nullable();
            $table->integer('use_count')->default(1);
            $table->boolean('is_trusted')->default(false);
            $table->boolean('is_blocked')->default(false);
            $table->timestamp('first_seen_at');
            $table->timestamp('last_seen_at');
            $table->timestamps();

            // Indexes
            $table->index('fingerprint_hash');
            $table->index(['user_id', 'fingerprint_hash']);
            $table->index('is_trusted');
            $table->index('is_blocked');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('device_fingerprints');
    }
};
