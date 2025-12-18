<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * SL-004: Confirmation Reminders tracking
     */
    public function up(): void
    {
        Schema::create('confirmation_reminders', function (Blueprint $table) {
            $table->id();

            // Relationship
            $table->foreignId('booking_confirmation_id')
                ->constrained('booking_confirmations')
                ->onDelete('cascade');

            // Reminder details
            $table->enum('type', ['email', 'sms', 'push'])->index();
            $table->enum('recipient_type', ['worker', 'business'])->index();

            // Tracking
            $table->timestamp('sent_at');
            $table->boolean('delivered')->default(false);
            $table->timestamp('delivered_at')->nullable();
            $table->text('failure_reason')->nullable();

            // Reference to sent notification (if applicable)
            $table->string('notification_id')->nullable();

            $table->timestamps();

            // Indexes with shortened names to avoid MySQL identifier length limit
            $table->index(['booking_confirmation_id', 'type'], 'conf_reminders_conf_id_type_idx');
            $table->index(['booking_confirmation_id', 'recipient_type'], 'conf_reminders_conf_id_recipient_idx');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('confirmation_reminders');
    }
};
