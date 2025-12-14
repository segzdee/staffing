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
        Schema::table('shifts', function (Blueprint $table) {
            // Only add fields that don't already exist (surge_multiplier already exists)
            $table->boolean('in_market')->default(true)->after('status');
            $table->boolean('is_demo')->default(false)->after('in_market');
            $table->timestamp('market_posted_at')->nullable()->after('is_demo');
            $table->boolean('instant_claim_enabled')->default(false)->after('is_demo');
            $table->integer('market_views')->default(0)->after('instant_claim_enabled');
            $table->integer('market_applications')->default(0)->after('market_views');

            // Composite index for market queries (using shift_date since start_datetime doesn't exist)
            $table->index(['in_market', 'status', 'shift_date'], 'idx_market_shifts');
            $table->index('is_demo');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('shifts', function (Blueprint $table) {
            $table->dropIndex('idx_market_shifts');
            $table->dropIndex(['is_demo']);
            $table->dropColumn([
                'in_market',
                'is_demo',
                'market_posted_at',
                'instant_claim_enabled',
                'market_views',
                'market_applications'
            ]);
        });
    }
};
