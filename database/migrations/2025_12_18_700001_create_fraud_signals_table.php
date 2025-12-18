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
        Schema::create('fraud_signals', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->string('signal_type'); // velocity, device, location, behavior, identity
            $table->string('signal_code'); // RAPID_SIGNUPS, DEVICE_MISMATCH, etc.
            $table->integer('severity')->default(1); // 1-10
            $table->json('signal_data')->nullable();
            $table->string('ip_address')->nullable();
            $table->string('device_fingerprint')->nullable();
            $table->string('user_agent')->nullable();
            $table->boolean('is_resolved')->default(false);
            $table->text('resolution_notes')->nullable();
            $table->timestamps();

            // Indexes
            $table->index('signal_type');
            $table->index('signal_code');
            $table->index('severity');
            $table->index('is_resolved');
            $table->index(['user_id', 'signal_type']);
            $table->index(['user_id', 'created_at']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('fraud_signals');
    }
};
