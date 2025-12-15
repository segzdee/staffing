<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateShiftApplicationsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('shift_applications', function (Blueprint $table) {
            $table->id();

            // Relationships
            $table->foreignId('shift_id')->constrained('shifts')->onDelete('cascade');
            $table->foreignId('worker_id')->constrained('users')->onDelete('cascade');

            // Application status
            $table->enum('status', [
                'pending',
                'accepted',
                'rejected',
                'withdrawn'
            ])->default('pending')->index();

            // Application details
            $table->text('application_note')->nullable();

            // Timestamps
            $table->timestamp('applied_at')->useCurrent();
            $table->timestamp('responded_at')->nullable();
            $table->timestamps();

            // Indexes
            $table->index('shift_id');
            $table->index('worker_id');
            $table->index(['shift_id', 'status']);
            $table->index(['worker_id', 'status']);

            // Unique constraint: worker can only apply once per shift
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
        Schema::dropIfExists('shift_applications');
    }
}
