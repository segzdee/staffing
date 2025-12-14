<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateShiftTemplatesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('shift_templates', function (Blueprint $table) {
            $table->id();
            $table->foreignId('business_id')->constrained('users')->onDelete('cascade');

            // Template details
            $table->string('template_name');
            $table->text('description')->nullable();

            // Shift configuration (same fields as shifts table)
            $table->string('title');
            $table->text('shift_description');
            $table->enum('industry', ['hospitality', 'healthcare', 'retail', 'events', 'warehouse', 'professional']);

            // Location
            $table->string('location_address');
            $table->string('location_city');
            $table->string('location_state');
            $table->string('location_country');
            $table->decimal('location_lat', 10, 8)->nullable();
            $table->decimal('location_lng', 11, 8)->nullable();

            // Timing (no specific date, just time and duration)
            $table->time('start_time');
            $table->time('end_time');
            $table->decimal('duration_hours', 5, 2);

            // Compensation
            $table->decimal('base_rate', 10, 2);
            $table->enum('urgency_level', ['normal', 'urgent', 'critical'])->default('normal');

            // Staffing
            $table->integer('required_workers')->default(1);

            // Requirements
            $table->json('requirements')->nullable();
            $table->string('dress_code')->nullable();
            $table->text('parking_info')->nullable();
            $table->text('break_info')->nullable();
            $table->text('special_instructions')->nullable();

            // Auto-renewal settings
            $table->boolean('auto_renew')->default(false);
            $table->enum('recurrence_pattern', ['daily', 'weekly', 'biweekly', 'monthly'])->nullable();
            $table->json('recurrence_days')->nullable(); // For weekly: ['monday', 'wednesday', 'friday']
            $table->date('recurrence_start_date')->nullable();
            $table->date('recurrence_end_date')->nullable();

            // Usage tracking
            $table->integer('times_used')->default(0);
            $table->timestamp('last_used_at')->nullable();

            $table->timestamps();

            // Indexes
            $table->index('business_id');
            $table->index(['business_id', 'industry']);
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('shift_templates');
    }
}
