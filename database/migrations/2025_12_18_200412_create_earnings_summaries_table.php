<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     * WKR-006: Earnings Dashboard - Cached Aggregate Summaries
     */
    public function up(): void
    {
        Schema::create('earnings_summaries', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->enum('period_type', ['daily', 'weekly', 'monthly', 'yearly']);
            $table->date('period_start');
            $table->date('period_end');
            $table->integer('shifts_completed')->default(0);
            $table->decimal('total_hours', 8, 2)->default(0);
            $table->decimal('gross_earnings', 12, 2)->default(0);
            $table->decimal('total_fees', 10, 2)->default(0);
            $table->decimal('total_taxes', 10, 2)->default(0);
            $table->decimal('net_earnings', 12, 2)->default(0);
            $table->decimal('avg_hourly_rate', 8, 2)->default(0);
            $table->timestamps();

            // Unique constraint to prevent duplicate summaries
            $table->unique(['user_id', 'period_type', 'period_start'], 'earnings_summaries_unique');

            // Indexes for efficient querying
            $table->index(['user_id', 'period_type']);
            $table->index('period_start');
            $table->index('period_end');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('earnings_summaries');
    }
};
