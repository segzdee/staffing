<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateBusinessCancellationLogsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('business_cancellation_logs', function (Blueprint $table) {
            $table->id();

            // Relationships
            $table->foreignId('business_profile_id')->constrained('business_profiles')->onDelete('cascade');
            $table->foreignId('shift_id')->constrained('shifts')->onDelete('cascade');
            $table->foreignId('cancelled_by_user_id')->constrained('users')->onDelete('cascade');

            // Cancellation details
            $table->enum('cancellation_type', [
                'on_time',      // >24 hours notice
                'late',         // <24 hours notice
                'no_show',      // shift already started
                'emergency'     // system-approved emergency
            ]);
            $table->text('cancellation_reason')->nullable();
            $table->integer('hours_before_shift')->nullable(); // hours notice given

            // Shift information (snapshot)
            $table->timestamp('shift_start_time');
            $table->timestamp('shift_end_time');
            $table->integer('shift_pay_rate'); // in cents
            $table->string('shift_role')->nullable();

            // Financial impact
            $table->integer('cancellation_fee')->default(0); // in cents
            $table->boolean('fee_waived')->default(false);
            $table->text('fee_waiver_reason')->nullable();

            // Pattern tracking
            $table->integer('total_cancellations_at_time')->default(0); // running total
            $table->integer('cancellations_last_30_days_at_time')->default(0);
            $table->decimal('cancellation_rate_at_time', 5, 2)->default(0.00);

            // Actions taken
            $table->boolean('warning_issued')->default(false);
            $table->boolean('escrow_increased')->default(false);
            $table->boolean('credit_suspended')->default(false);
            $table->timestamp('action_taken_at')->nullable();

            // Timestamps
            $table->timestamps();

            // Indexes
            $table->index('business_profile_id');
            $table->index('shift_id');
            $table->index('cancellation_type');
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
        Schema::dropIfExists('business_cancellation_logs');
    }
}
