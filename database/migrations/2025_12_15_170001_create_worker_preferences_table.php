<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     * STAFF-REG-009: Worker Availability Setup
     */
    public function up(): void
    {
        Schema::create('worker_preferences', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');

            // Work hour limits
            $table->integer('max_hours_per_week')->nullable()->default(40);
            $table->integer('max_shifts_per_day')->nullable()->default(1);
            $table->decimal('min_hours_per_shift', 4, 2)->nullable()->default(2.00);

            // Travel preferences
            $table->integer('max_travel_distance')->nullable()->default(25); // in km/miles
            $table->enum('distance_unit', ['km', 'miles'])->default('km');

            // Shift type preferences (morning, afternoon, evening, overnight)
            $table->json('preferred_shift_types')->nullable();

            // Rate preferences
            $table->decimal('min_hourly_rate', 10, 2)->nullable();
            $table->string('preferred_currency', 3)->default('USD');

            // Work environment preferences
            $table->json('preferred_industries')->nullable();
            $table->json('preferred_roles')->nullable();
            $table->json('excluded_businesses')->nullable(); // Businesses worker doesn't want to work for

            // Notification preferences for shifts
            $table->boolean('notify_new_shifts')->default(true);
            $table->boolean('notify_matching_shifts')->default(true);
            $table->boolean('notify_urgent_shifts')->default(true);
            $table->integer('advance_notice_hours')->default(24); // Min hours before shift start

            // Auto-accept settings
            $table->boolean('auto_accept_invitations')->default(false);
            $table->boolean('auto_accept_recurring')->default(false);

            // Timestamps
            $table->timestamps();

            // Indexes
            $table->unique('user_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('worker_preferences');
    }
};
