<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     * STAFF-REG-009: Worker Availability - Date Overrides
     */
    public function up(): void
    {
        Schema::create('worker_availability_overrides', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');

            // Specific date override
            $table->date('date');

            // Override type
            $table->enum('type', ['available', 'unavailable', 'custom'])->default('custom');

            // Time slots for custom availability (null means all day)
            $table->time('start_time')->nullable();
            $table->time('end_time')->nullable();

            // If this is a one-time availability
            $table->boolean('is_one_time')->default(true);

            // Reason/Notes
            $table->string('reason')->nullable();
            $table->text('notes')->nullable();

            // Priority over regular schedule
            $table->integer('priority')->default(1);

            $table->timestamps();

            // Indexes
            $table->index(['user_id', 'date']);
            $table->unique(['user_id', 'date', 'start_time']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('worker_availability_overrides');
    }
};
