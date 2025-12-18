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
     * Tracks user reports of inappropriate communications.
     */
    public function up(): void
    {
        Schema::create('communication_reports', function (Blueprint $table) {
            $table->id();
            $table->string('reportable_type'); // Message, Conversation
            $table->unsignedBigInteger('reportable_id');
            $table->foreignId('reporter_id')->constrained('users')->onDelete('cascade');
            $table->foreignId('reported_user_id')->constrained('users')->onDelete('cascade');
            $table->enum('reason', ['harassment', 'spam', 'inappropriate', 'threatening', 'pii_sharing', 'other']);
            $table->text('description')->nullable();
            $table->enum('status', ['pending', 'investigating', 'resolved', 'dismissed'])->default('pending');
            $table->foreignId('resolved_by')->nullable()->constrained('users');
            $table->text('resolution_notes')->nullable();
            $table->timestamp('resolved_at')->nullable();
            $table->timestamps();

            $table->index(['reportable_type', 'reportable_id']);
            $table->index('reporter_id');
            $table->index('reported_user_id');
            $table->index('status');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('communication_reports');
    }
};
