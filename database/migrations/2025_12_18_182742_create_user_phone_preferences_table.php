<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * COM-004: User Phone and Messaging Preferences
     * Stores user preferences for SMS vs WhatsApp communication.
     */
    public function up(): void
    {
        Schema::create('user_phone_preferences', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->string('phone_number', 20);
            $table->string('country_code', 5)->default('+1');
            $table->boolean('whatsapp_enabled')->default(false);
            $table->boolean('sms_enabled')->default(true);
            $table->enum('preferred_channel', ['sms', 'whatsapp'])->default('sms');
            $table->boolean('verified')->default(false);
            $table->timestamp('verified_at')->nullable();
            $table->string('verification_method')->nullable()->comment('sms_code, whatsapp_code, manual');
            $table->boolean('marketing_opt_in')->default(false);
            $table->boolean('transactional_opt_in')->default(true);
            $table->boolean('urgent_alerts_opt_in')->default(true);
            $table->json('quiet_hours')->nullable()->comment('Do not disturb hours: {start: "22:00", end: "07:00", timezone: "UTC"}');
            $table->string('whatsapp_opt_in_message_id')->nullable()->comment('Message ID when user opted in');
            $table->timestamp('whatsapp_opted_in_at')->nullable();
            $table->timestamp('whatsapp_opted_out_at')->nullable();
            $table->timestamps();

            $table->unique('user_id');
            $table->index('phone_number');
            $table->index(['preferred_channel', 'verified']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('user_phone_preferences');
    }
};
