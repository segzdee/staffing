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
        Schema::create('agency_tier_history', function (Blueprint $table) {
            $table->id();
            $table->foreignId('agency_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('from_tier_id')->nullable()->constrained('agency_tiers')->nullOnDelete();
            $table->foreignId('to_tier_id')->constrained('agency_tiers')->cascadeOnDelete();
            $table->enum('change_type', ['upgrade', 'downgrade', 'initial']);
            $table->json('metrics_at_change');
            $table->text('notes')->nullable();
            $table->foreignId('processed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index(['agency_id', 'created_at']);
            $table->index('change_type');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('agency_tier_history');
    }
};
