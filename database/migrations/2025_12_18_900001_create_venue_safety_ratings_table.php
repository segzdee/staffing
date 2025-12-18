<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * SAF-004: Venue Safety Ratings Migration
 *
 * Creates the venue_safety_ratings table for workers to rate
 * venue safety after completing shifts.
 */
return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('venue_safety_ratings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('venue_id')->constrained()->onDelete('cascade');
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->foreignId('shift_id')->nullable()->constrained()->onDelete('set null');
            $table->integer('overall_safety')->unsigned(); // 1-5
            $table->integer('lighting_rating')->unsigned()->nullable(); // 1-5
            $table->integer('parking_safety')->unsigned()->nullable(); // 1-5
            $table->integer('emergency_exits')->unsigned()->nullable(); // 1-5
            $table->integer('staff_support')->unsigned()->nullable(); // 1-5
            $table->integer('equipment_condition')->unsigned()->nullable(); // 1-5
            $table->text('safety_concerns')->nullable();
            $table->text('positive_notes')->nullable();
            $table->boolean('would_return')->default(true);
            $table->boolean('is_anonymous')->default(false);
            $table->timestamps();

            // Each worker can only rate a venue once per shift
            $table->unique(['venue_id', 'user_id', 'shift_id'], 'venue_user_shift_unique');

            // Indexes for efficient queries
            $table->index(['venue_id', 'overall_safety']);
            $table->index(['venue_id', 'created_at']);
            $table->index('user_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('venue_safety_ratings');
    }
};
