<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * COM-005: Communication Compliance
     * Stores blocked phrases for content moderation.
     */
    public function up(): void
    {
        Schema::create('blocked_phrases', function (Blueprint $table) {
            $table->id();
            $table->string('phrase');
            $table->enum('type', ['profanity', 'harassment', 'spam', 'pii', 'contact_info', 'custom']);
            $table->enum('action', ['block', 'flag', 'redact'])->default('flag');
            $table->boolean('is_regex')->default(false);
            $table->boolean('case_sensitive')->default(false);
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->index('type');
            $table->index('is_active');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('blocked_phrases');
    }
};
