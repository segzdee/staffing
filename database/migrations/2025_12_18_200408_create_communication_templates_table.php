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
        Schema::create('communication_templates', function (Blueprint $table) {
            $table->id();
            $table->foreignId('business_id')->constrained('users')->onDelete('cascade');
            $table->string('name');
            $table->string('slug');
            $table->enum('type', ['shift_instruction', 'welcome', 'reminder', 'thank_you', 'feedback_request', 'custom']);
            $table->enum('channel', ['email', 'sms', 'in_app', 'all']);
            $table->string('subject')->nullable();
            $table->text('body');
            $table->json('variables')->nullable(); // Available merge tags
            $table->boolean('is_default')->default(false);
            $table->boolean('is_active')->default(true);
            $table->boolean('is_system')->default(false); // System templates cannot be edited
            $table->integer('usage_count')->default(0);
            $table->timestamps();

            $table->unique(['business_id', 'slug']);
            $table->index(['business_id', 'type']);
            $table->index(['business_id', 'is_active']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('communication_templates');
    }
};
