<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateWorkerProfilesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('worker_profiles', function (Blueprint $table) {
            $table->id();

            // User relationship
            $table->foreignId('user_id')->constrained('users')->onDelete('cascade')->unique();

            // Profile information
            $table->text('bio')->nullable();
            $table->decimal('hourly_rate_min', 10, 2)->nullable();
            $table->decimal('hourly_rate_max', 10, 2)->nullable();

            // Industry preferences
            $table->json('industries')->nullable(); // array of industry preferences

            // Availability
            $table->json('availability_schedule')->nullable(); // weekly availability {monday: {start: '09:00', end: '17:00'}}

            // Transportation and location
            $table->enum('transportation', ['car', 'bike', 'public_transit', 'walking'])->default('public_transit');
            $table->integer('max_commute_distance')->default(10); // in miles/km

            // Experience
            $table->integer('years_experience')->default(0);

            // Performance metrics (cached for quick access)
            $table->decimal('rating_average', 3, 2)->default(0.00);
            $table->integer('total_shifts_completed')->default(0);
            $table->decimal('reliability_score', 3, 2)->default(0.00); // 0-1 score based on no-shows, cancellations
            $table->integer('total_no_shows')->default(0);
            $table->integer('total_cancellations')->default(0);

            // Verification
            $table->enum('background_check_status', [
                'not_started',
                'pending',
                'approved',
                'rejected'
            ])->default('not_started');
            $table->date('background_check_date')->nullable();
            $table->text('background_check_notes')->nullable();

            // Timestamps
            $table->timestamps();

            // Indexes
            $table->index('user_id');
            $table->index('rating_average');
            $table->index('background_check_status');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('worker_profiles');
    }
}
