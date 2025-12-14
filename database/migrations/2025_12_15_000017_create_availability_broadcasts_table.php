<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateAvailabilityBroadcastsTable extends Migration
{
    public function up()
    {
        Schema::create('availability_broadcasts', function (Blueprint $table) {
            $table->id();

            $table->foreignId('worker_id')->constrained('users')->onDelete('cascade');

            $table->enum('broadcast_type', ['immediate', 'scheduled'])->default('immediate');
            $table->timestamp('available_from');
            $table->timestamp('available_to');

            $table->json('industries')->nullable(); // industries worker is available for
            $table->integer('max_distance')->nullable(); // max commute distance
            $table->text('message')->nullable();

            $table->enum('status', ['active', 'expired', 'cancelled'])->default('active')->index();

            $table->timestamps();

            // Indexes
            $table->index('worker_id');
            $table->index(['status', 'available_from']);
            $table->index('available_from');
            $table->index('available_to');
        });
    }

    public function down()
    {
        Schema::dropIfExists('availability_broadcasts');
    }
}
