<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateWorkerAvailabilitySchedulesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('worker_availability_schedules', function (Blueprint $table) {
            $table->id();
            $table->foreignId('worker_id')->constrained('users')->onDelete('cascade');

            // Recurring availability (day of week)
            $table->enum('day_of_week', ['monday', 'tuesday', 'wednesday', 'thursday', 'friday', 'saturday', 'sunday']);
            $table->time('start_time');
            $table->time('end_time');
            $table->boolean('is_available')->default(true);

            // Shift type preferences
            $table->json('preferred_shift_types')->nullable(); // ['morning', 'afternoon', 'evening', 'night']

            // Recurrence pattern
            $table->enum('recurrence', ['weekly', 'biweekly', 'monthly'])->default('weekly');
            $table->date('effective_from')->nullable();
            $table->date('effective_until')->nullable();

            $table->timestamps();

            // Indexes
            $table->index('worker_id');
            $table->index(['worker_id', 'day_of_week']);
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('worker_availability_schedules');
    }
}
