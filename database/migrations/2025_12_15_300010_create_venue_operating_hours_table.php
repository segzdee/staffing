<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * BIZ-REG-006: Venue Operating Hours
 *
 * Stores operating hours for each day of the week for venues.
 * Supports multiple time slots per day (e.g., split shifts).
 */
class CreateVenueOperatingHoursTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('venue_operating_hours', function (Blueprint $table) {
            $table->id();
            $table->foreignId('venue_id')->constrained('venues')->onDelete('cascade');

            // Day of week (0 = Sunday, 6 = Saturday)
            $table->tinyInteger('day_of_week');

            // Time slots
            $table->time('open_time');
            $table->time('close_time');

            // Is this the primary time slot for this day?
            $table->boolean('is_primary')->default(true);

            // Is the venue open on this day?
            $table->boolean('is_open')->default(true);

            // Notes for this day (e.g., "Kitchen closes at 10pm")
            $table->string('notes')->nullable();

            $table->timestamps();

            // Indexes
            $table->index(['venue_id', 'day_of_week']);
            $table->index('is_open');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('venue_operating_hours');
    }
}
