<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateUrgentShiftRequestsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('urgent_shift_requests', function (Blueprint $table) {
            $table->id();
            $table->bigInteger('shift_id')->unsigned();
            $table->bigInteger('business_id')->unsigned();

            // Urgency details
            $table->string('urgency_reason'); // time_constraint, low_fill_rate, cancellation
            $table->decimal('fill_percentage', 5, 2)->default(0.00);
            $table->integer('hours_until_shift')->default(0);
            $table->timestamp('shift_start_time');

            // SLA tracking
            $table->timestamp('detected_at');
            $table->timestamp('first_agency_notified_at')->nullable();
            $table->timestamp('sla_deadline')->nullable(); // 30 minutes after first notification
            $table->boolean('sla_met')->default(false);
            $table->boolean('sla_breached')->default(false);

            // Routing status
            $table->enum('status', ['pending', 'routed', 'accepted', 'filled', 'failed', 'expired'])->default('pending');
            $table->integer('agencies_notified')->default(0);
            $table->integer('agencies_responded')->default(0);
            $table->json('notified_agency_ids')->nullable(); // Array of agency IDs

            // Response tracking
            $table->bigInteger('accepted_by_agency_id')->unsigned()->nullable();
            $table->timestamp('accepted_at')->nullable();
            $table->integer('response_time_minutes')->nullable();

            // Escalation tracking
            $table->boolean('escalated')->default(false);
            $table->timestamp('escalated_at')->nullable();
            $table->text('escalation_notes')->nullable();

            // Resolution
            $table->timestamp('resolved_at')->nullable();
            $table->string('resolution_type')->nullable(); // filled, expired, cancelled
            $table->text('resolution_notes')->nullable();

            $table->timestamps();

            // Indexes
            $table->index('shift_id');
            $table->index('business_id');
            $table->index('status');
            $table->index('detected_at');
            $table->index('sla_deadline');
            $table->index(['status', 'sla_deadline'], 'active_requests_idx');

            // Foreign keys
            $table->foreign('shift_id')
                ->references('id')
                ->on('shifts')
                ->onDelete('cascade');

            $table->foreign('business_id')
                ->references('id')
                ->on('users')
                ->onDelete('cascade');

            $table->foreign('accepted_by_agency_id')
                ->references('id')
                ->on('users')
                ->onDelete('set null');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('urgent_shift_requests');
    }
}
