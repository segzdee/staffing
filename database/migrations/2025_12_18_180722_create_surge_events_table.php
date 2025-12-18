<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('surge_events', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->text('description')->nullable();
            $table->string('region')->nullable(); // city or area
            $table->date('start_date');
            $table->date('end_date');
            $table->decimal('surge_multiplier', 3, 2)->default(1.50);
            $table->enum('event_type', ['concert', 'sports', 'conference', 'festival', 'holiday', 'weather', 'other']);
            $table->integer('expected_demand_increase')->nullable(); // percentage
            $table->boolean('is_active')->default(true);
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index(['start_date', 'end_date', 'region']);
            $table->index(['is_active', 'start_date']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('surge_events');
    }
};
