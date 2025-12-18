<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * WKR-007: Worker Career Tiers System - Tier change history tracking
     */
    public function up(): void
    {
        Schema::create('worker_tier_history', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->foreignId('from_tier_id')->nullable()->constrained('worker_tiers')->nullOnDelete();
            $table->foreignId('to_tier_id')->constrained('worker_tiers')->onDelete('cascade');
            $table->enum('change_type', ['upgrade', 'downgrade', 'initial']);
            $table->json('metrics_at_change');
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->index('user_id');
            $table->index('change_type');
            $table->index('created_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('worker_tier_history');
    }
};
