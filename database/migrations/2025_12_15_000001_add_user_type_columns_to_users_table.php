<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddUserTypeColumnsToUsersTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('users', function (Blueprint $table) {
            // Add user type enum
            $table->enum('user_type', ['worker', 'business', 'agency', 'ai_agent', 'admin'])
                ->default('worker');

            // Verification flags for different user types
            $table->boolean('is_verified_worker')->default(false);
            $table->boolean('is_verified_business')->default(false);

            // Onboarding tracking
            $table->string('onboarding_step')->nullable();
            $table->boolean('onboarding_completed')->default(false);

            // Notification preferences (JSON for flexible settings)
            $table->json('notification_preferences')->nullable();

            // Availability settings for workers
            $table->json('availability_schedule')->nullable();

            // Location preferences for workers (max commute distance in miles)
            $table->integer('max_commute_distance')->nullable();

            // Rating averages (cached for performance)
            $table->decimal('rating_as_worker', 3, 2)->default(0.00);
            $table->decimal('rating_as_business', 3, 2)->default(0.00);

            // Performance metrics
            $table->integer('total_shifts_completed')->default(0);
            $table->integer('total_shifts_posted')->default(0);
            $table->decimal('reliability_score', 3, 2)->default(0.00);

            // Add indexes for common queries
            $table->index('user_type');
            $table->index('onboarding_completed');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('users', function (Blueprint $table) {
            // Drop columns (indexes drop automatically with columns in MySQL)
            $table->dropColumn([
                'user_type',
                'is_verified_worker',
                'is_verified_business',
                'onboarding_step',
                'onboarding_completed',
                'notification_preferences',
                'availability_schedule',
                'max_commute_distance',
                'rating_as_worker',
                'rating_as_business',
                'total_shifts_completed',
                'total_shifts_posted',
                'reliability_score',
            ]);
        });
    }
}
