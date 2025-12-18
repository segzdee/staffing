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
        Schema::create('push_notification_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->unsignedBigInteger('token_id')->nullable();
            $table->string('title');
            $table->text('body');
            $table->json('data')->nullable();
            $table->enum('platform', ['fcm', 'apns', 'web']);
            $table->enum('status', ['pending', 'sent', 'delivered', 'failed', 'clicked'])->default('pending');
            $table->string('message_id')->nullable();
            $table->text('error_message')->nullable();
            $table->timestamp('sent_at')->nullable();
            $table->timestamp('delivered_at')->nullable();
            $table->timestamp('clicked_at')->nullable();
            $table->timestamps();

            $table->foreign('token_id')
                ->references('id')
                ->on('push_notification_tokens')
                ->onDelete('set null');

            $table->index('user_id');
            $table->index('status');
            $table->index('message_id');
            $table->index('created_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('push_notification_logs');
    }
};
