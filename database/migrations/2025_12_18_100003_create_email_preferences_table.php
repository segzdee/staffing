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
        Schema::create('email_preferences', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->boolean('marketing_emails')->default(true);
            $table->boolean('shift_notifications')->default(true);
            $table->boolean('payment_notifications')->default(true);
            $table->boolean('weekly_digest')->default(true);
            $table->boolean('tips_and_updates')->default(true);
            $table->string('unsubscribe_token')->unique();
            $table->timestamps();

            $table->unique('user_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('email_preferences');
    }
};
