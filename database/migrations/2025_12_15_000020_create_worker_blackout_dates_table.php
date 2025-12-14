<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateWorkerBlackoutDatesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('worker_blackout_dates', function (Blueprint $table) {
            $table->id();
            $table->foreignId('worker_id')->constrained('users')->onDelete('cascade');

            // Blackout date range
            $table->date('start_date');
            $table->date('end_date');

            // Optional reason
            $table->string('reason')->nullable();
            $table->text('notes')->nullable();

            // Type of blackout
            $table->enum('type', ['vacation', 'personal', 'medical', 'other'])->default('personal');

            $table->timestamps();

            // Indexes
            $table->index('worker_id');
            $table->index(['worker_id', 'start_date', 'end_date']);
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('worker_blackout_dates');
    }
}
