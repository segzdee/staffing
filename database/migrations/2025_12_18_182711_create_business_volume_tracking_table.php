<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * FIN-001: Business Volume Tracking
 *
 * Tracks monthly volume metrics for each business to determine
 * their discount tier eligibility. Records are created per month
 * and updated as shifts are posted and completed.
 */
return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('business_volume_tracking', function (Blueprint $table) {
            $table->id();
            $table->foreignId('business_id')->constrained('users')->onDelete('cascade');
            $table->date('month'); // First day of the month (e.g., 2025-01-01)
            $table->integer('shifts_posted')->default(0);
            $table->integer('shifts_filled')->default(0);
            $table->integer('shifts_completed')->default(0);
            $table->integer('shifts_cancelled')->default(0);
            $table->decimal('total_spend', 12, 2)->default(0); // Total amount spent
            $table->decimal('platform_fees_paid', 10, 2)->default(0); // Fees actually paid
            $table->decimal('platform_fees_without_discount', 10, 2)->default(0); // What fees would have been without discount
            $table->foreignId('applied_tier_id')->nullable()->constrained('volume_discount_tiers')->onDelete('set null');
            $table->decimal('discount_amount', 10, 2)->default(0); // Total savings from volume discount
            $table->decimal('average_shift_value', 10, 2)->default(0); // Average spend per shift
            $table->integer('unique_workers_hired')->default(0); // Unique workers hired in the month
            $table->integer('repeat_workers')->default(0); // Workers hired more than once
            $table->json('daily_breakdown')->nullable(); // Optional daily stats
            $table->timestamp('tier_qualified_at')->nullable(); // When they qualified for the tier
            $table->timestamp('tier_notified_at')->nullable(); // When they were notified of tier change
            $table->timestamps();

            // Composite unique constraint
            $table->unique(['business_id', 'month']);

            // Indexes for querying
            $table->index('month');
            $table->index('applied_tier_id');
            $table->index(['business_id', 'month']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('business_volume_tracking');
    }
};
