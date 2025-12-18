<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * WKR-004: Add category rating averages to worker and business profiles
     * Worker categories: punctuality, quality, professionalism, reliability
     * Business categories: punctuality, communication, professionalism, payment_reliability
     */
    public function up(): void
    {
        // Add category averages to worker_profiles
        Schema::table('worker_profiles', function (Blueprint $table) {
            // Category averages for workers (rated by businesses)
            $table->decimal('avg_punctuality', 3, 2)->nullable()->after('rating_average');
            $table->decimal('avg_quality', 3, 2)->nullable()->after('avg_punctuality');
            $table->decimal('avg_professionalism', 3, 2)->nullable()->after('avg_quality');
            $table->decimal('avg_reliability', 3, 2)->nullable()->after('avg_professionalism');

            // Weighted overall rating based on category weights
            $table->decimal('weighted_rating', 3, 2)->nullable()->after('avg_reliability');

            // Total ratings count for calculating averages
            $table->unsignedInteger('total_ratings_count')->default(0)->after('weighted_rating');

            // Add indexes for performance on commonly queried fields
            $table->index('weighted_rating');
            $table->index('avg_punctuality');
            $table->index('avg_quality');
        });

        // Add category averages to business_profiles
        Schema::table('business_profiles', function (Blueprint $table) {
            // Category averages for businesses (rated by workers)
            $table->decimal('avg_punctuality', 3, 2)->nullable()->after('rating_average');
            $table->decimal('avg_communication', 3, 2)->nullable()->after('avg_punctuality');
            $table->decimal('avg_professionalism', 3, 2)->nullable()->after('avg_communication');
            $table->decimal('avg_payment_reliability', 3, 2)->nullable()->after('avg_professionalism');

            // Weighted overall rating based on category weights
            $table->decimal('weighted_rating', 3, 2)->nullable()->after('avg_payment_reliability');

            // Total ratings count for calculating averages
            $table->unsignedInteger('total_ratings_count')->default(0)->after('weighted_rating');

            // Add indexes for performance
            $table->index('weighted_rating');
            $table->index('avg_communication');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('worker_profiles', function (Blueprint $table) {
            $table->dropIndex(['weighted_rating']);
            $table->dropIndex(['avg_punctuality']);
            $table->dropIndex(['avg_quality']);

            $table->dropColumn([
                'avg_punctuality',
                'avg_quality',
                'avg_professionalism',
                'avg_reliability',
                'weighted_rating',
                'total_ratings_count',
            ]);
        });

        Schema::table('business_profiles', function (Blueprint $table) {
            $table->dropIndex(['weighted_rating']);
            $table->dropIndex(['avg_communication']);

            $table->dropColumn([
                'avg_punctuality',
                'avg_communication',
                'avg_professionalism',
                'avg_payment_reliability',
                'weighted_rating',
                'total_ratings_count',
            ]);
        });
    }
};
