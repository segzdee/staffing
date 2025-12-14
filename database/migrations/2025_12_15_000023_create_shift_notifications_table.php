<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateShiftNotificationsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('shift_notifications', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->onDelete('cascade');
            $table->foreignId('shift_id')->nullable()->constrained('shifts')->onDelete('cascade');
            $table->foreignId('assignment_id')->nullable()->constrained('shift_assignments')->onDelete('cascade');

            // Notification details
            $table->enum('type', [
                'shift_assigned',
                'shift_cancelled',
                'shift_updated',
                'shift_reminder_2h',
                'shift_reminder_30m',
                'application_received',
                'application_accepted',
                'application_rejected',
                'shift_filled',
                'shift_starting_soon',
                'worker_checked_in',
                'worker_no_show',
                'payment_released',
                'shift_swap_offered',
                'shift_swap_accepted',
                'shift_invitation',
                'worker_cancelled',
                'emergency_alert'
            ]);

            $table->string('title');
            $table->text('message');
            $table->json('data')->nullable(); // Additional data

            // Notification channels
            $table->boolean('sent_push')->default(false);
            $table->boolean('sent_email')->default(false);
            $table->boolean('sent_sms')->default(false);

            // Status
            $table->boolean('read')->default(false);
            $table->timestamp('read_at')->nullable();
            $table->timestamp('sent_at')->nullable();

            $table->timestamps();

            // Indexes
            $table->index('user_id');
            $table->index(['user_id', 'read']);
            $table->index(['shift_id', 'type']);
            $table->index('type');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('shift_notifications');
    }
}
