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
     * Tracks moderation actions on messages for audit and compliance purposes.
     */
    public function up(): void
    {
        Schema::create('message_moderation_logs', function (Blueprint $table) {
            $table->id();
            $table->string('moderatable_type'); // Message, DisputeMessage
            $table->unsignedBigInteger('moderatable_id');
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->text('original_content');
            $table->text('moderated_content')->nullable();
            $table->json('detected_issues')->nullable(); // [{type, confidence, matched_text}]
            $table->enum('action', ['allowed', 'flagged', 'blocked', 'redacted'])->default('allowed');
            $table->enum('severity', ['low', 'medium', 'high', 'critical'])->default('low');
            $table->boolean('requires_review')->default(false);
            $table->foreignId('reviewed_by')->nullable()->constrained('users');
            $table->timestamp('reviewed_at')->nullable();
            $table->text('review_notes')->nullable();
            $table->timestamps();

            $table->index(['moderatable_type', 'moderatable_id']);
            $table->index(['action', 'requires_review']);
            $table->index(['severity', 'requires_review']);
            $table->index('user_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('message_moderation_logs');
    }
};
