<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     * COM-001: Enhance messages table for rich messaging support
     */
    public function up(): void
    {
        Schema::table('messages', function (Blueprint $table) {
            // Add sender_id as alias/replacement for from_user_id (for new code)
            // Keep from_user_id for backward compatibility

            // Add message type
            $table->enum('message_type', ['text', 'image', 'file', 'system'])
                ->default('text')
                ->after('message');

            // Add structured attachments JSON (replaces simple attachment_url for new messages)
            $table->json('attachments')->nullable()->after('attachment_type');

            // Add metadata for system messages, etc.
            $table->json('metadata')->nullable()->after('attachments');

            // Add edit tracking
            $table->boolean('is_edited')->default(false)->after('metadata');
            $table->timestamp('edited_at')->nullable()->after('is_edited');

            // Add soft deletes
            $table->softDeletes();

            // Indexes
            $table->index('message_type');
            $table->index('deleted_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('messages', function (Blueprint $table) {
            $table->dropIndex(['message_type']);
            $table->dropIndex(['deleted_at']);
            $table->dropSoftDeletes();
            $table->dropColumn(['message_type', 'attachments', 'metadata', 'is_edited', 'edited_at']);
        });
    }
};
