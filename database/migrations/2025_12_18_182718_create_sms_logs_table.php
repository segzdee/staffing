<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * COM-004: SMS/WhatsApp Alert Logging
     * Tracks all outbound SMS and WhatsApp messages with delivery status.
     */
    public function up(): void
    {
        Schema::create('sms_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable()->constrained()->onDelete('set null');
            $table->string('phone_number', 20)->index();
            $table->enum('channel', ['sms', 'whatsapp'])->default('sms');
            $table->enum('type', ['otp', 'shift_reminder', 'urgent_alert', 'marketing', 'transactional']);
            $table->text('content');
            $table->string('template_id')->nullable()->comment('WhatsApp template ID or SMS template name');
            $table->json('template_params')->nullable()->comment('Parameters passed to template');
            $table->string('provider', 50)->nullable()->comment('twilio, vonage, messagebird, meta');
            $table->string('provider_message_id')->nullable()->unique()->comment('External message ID');
            $table->enum('status', ['pending', 'queued', 'sent', 'delivered', 'failed', 'read'])->default('pending');
            $table->integer('segments')->default(1)->comment('SMS segment count');
            $table->decimal('cost', 8, 4)->nullable()->comment('Cost in USD');
            $table->string('currency', 3)->default('USD');
            $table->text('error_message')->nullable();
            $table->string('error_code', 50)->nullable();
            $table->integer('retry_count')->default(0);
            $table->timestamp('queued_at')->nullable();
            $table->timestamp('sent_at')->nullable();
            $table->timestamp('delivered_at')->nullable();
            $table->timestamp('read_at')->nullable();
            $table->timestamp('failed_at')->nullable();
            $table->timestamps();

            $table->index(['channel', 'status']);
            $table->index(['user_id', 'channel', 'created_at']);
            $table->index(['type', 'created_at']);
            $table->index('sent_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('sms_logs');
    }
};
