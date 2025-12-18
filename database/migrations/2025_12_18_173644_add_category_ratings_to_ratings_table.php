<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * WKR-004: Add 4-category rating fields to ratings table
     * Categories for workers: punctuality, quality, professionalism, reliability
     * Categories for businesses: punctuality, communication, professionalism, payment_reliability
     */
    public function up(): void
    {
        Schema::table('ratings', function (Blueprint $table) {
            // Individual category ratings (1-5 scale)
            $table->unsignedTinyInteger('punctuality_rating')->nullable()->after('rating');
            $table->unsignedTinyInteger('quality_rating')->nullable()->after('punctuality_rating');
            $table->unsignedTinyInteger('professionalism_rating')->nullable()->after('quality_rating');
            $table->unsignedTinyInteger('reliability_rating')->nullable()->after('professionalism_rating');

            // Business-specific categories (for workers rating businesses)
            $table->unsignedTinyInteger('communication_rating')->nullable()->after('reliability_rating');
            $table->unsignedTinyInteger('payment_reliability_rating')->nullable()->after('communication_rating');

            // Calculated weighted score based on category weights
            $table->decimal('weighted_score', 3, 2)->nullable()->after('payment_reliability_rating');

            // Flag for low ratings requiring attention
            $table->boolean('is_flagged')->default(false)->after('weighted_score');
            $table->string('flag_reason')->nullable()->after('is_flagged');
            $table->timestamp('flagged_at')->nullable()->after('flag_reason');

            // Add indexes for performance
            $table->index('weighted_score');
            $table->index('is_flagged');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('ratings', function (Blueprint $table) {
            $table->dropIndex(['weighted_score']);
            $table->dropIndex(['is_flagged']);

            $table->dropColumn([
                'punctuality_rating',
                'quality_rating',
                'professionalism_rating',
                'reliability_rating',
                'communication_rating',
                'payment_reliability_rating',
                'weighted_score',
                'is_flagged',
                'flag_reason',
                'flagged_at',
            ]);
        });
    }
};
