<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateShiftAssignmentsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('shift_assignments', function (Blueprint $table) {
            $table->id();

            // Relationships
            $table->foreignId('shift_id')->constrained('shifts')->onDelete('cascade');
            $table->foreignId('worker_id')->constrained('users')->onDelete('cascade');
            $table->foreignId('assigned_by')->constrained('users')->onDelete('cascade'); // business or agent

            // Clock in/out tracking
            $table->timestamp('check_in_time')->nullable();
            $table->timestamp('check_out_time')->nullable();
            $table->decimal('hours_worked', 5, 2)->nullable();

            // Assignment status
            $table->enum('status', [
                'assigned',
                'checked_in',
                'checked_out',
                'completed',
                'no_show',
                'cancelled'
            ])->default('assigned')->index();

            // Timestamps
            $table->timestamps();

            // Indexes
            $table->index('shift_id');
            $table->index('worker_id');
            $table->index(['shift_id', 'status']);
            $table->index(['worker_id', 'status']);
            $table->index('check_in_time');
            $table->index('check_out_time');

            // Unique constraint: worker can only be assigned once per shift
            $table->unique(['shift_id', 'worker_id']);
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('shift_assignments');
    }
}
