<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateShiftsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('shifts', function (Blueprint $table) {
            $table->id();

            // Business posting the shift
            $table->foreignId('business_id')->constrained('users')->onDelete('cascade');

            // Basic shift information
            $table->string('title');
            $table->text('description');
            $table->enum('industry', [
                'hospitality',
                'healthcare',
                'retail',
                'events',
                'warehouse',
                'professional'
            ])->index();

            // Location information
            $table->string('location_address');
            $table->string('location_city');
            $table->string('location_state');
            $table->string('location_country');
            $table->decimal('location_lat', 10, 8)->nullable();
            $table->decimal('location_lng', 11, 8)->nullable();

            // Shift timing
            $table->date('shift_date')->index();
            $table->time('start_time');
            $table->time('end_time');
            $table->decimal('duration_hours', 5, 2);

            // Pricing
            $table->decimal('base_rate', 10, 2); // base hourly rate
            $table->decimal('dynamic_rate', 10, 2)->nullable(); // calculated with urgency/demand
            $table->decimal('final_rate', 10, 2); // what worker actually earns

            // Urgency and status
            $table->enum('urgency_level', ['normal', 'urgent', 'critical'])->default('normal');
            $table->enum('status', [
                'draft',
                'open',
                'assigned',
                'in_progress',
                'completed',
                'cancelled'
            ])->default('open')->index();

            // Worker capacity
            $table->integer('required_workers')->default(1);
            $table->integer('filled_workers')->default(0);

            // Requirements and details
            $table->json('requirements')->nullable(); // skills, certifications needed
            $table->string('dress_code')->nullable();
            $table->string('parking_info')->nullable();
            $table->string('break_info')->nullable();
            $table->text('special_instructions')->nullable();

            // AI Agent tracking
            $table->boolean('posted_by_agent')->default(false);
            $table->foreignId('agent_id')->nullable()->constrained('users')->onDelete('set null');

            // Timestamps
            $table->timestamps();
            $table->softDeletes();

            // Indexes for common queries
            $table->index('business_id');
            $table->index(['shift_date', 'status']);
            $table->index(['industry', 'status']);
            $table->index(['location_city', 'location_state']);
            $table->index('created_at');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('shifts');
    }
}
