<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     * GLO-005: GDPR/CCPA Compliance - Data Subject Requests
     */
    public function up(): void
    {
        Schema::create('data_subject_requests', function (Blueprint $table) {
            $table->id();
            $table->string('request_number')->unique(); // DSR-2024-00001
            $table->foreignId('user_id')->nullable()->constrained()->onDelete('set null');
            $table->string('email'); // for unregistered requesters
            $table->enum('type', ['access', 'rectification', 'erasure', 'portability', 'restriction', 'objection']);
            $table->enum('status', ['pending', 'verifying', 'processing', 'completed', 'rejected'])->default('pending');
            $table->text('description')->nullable();
            $table->string('verification_token')->nullable();
            $table->timestamp('verified_at')->nullable();
            $table->timestamp('due_date'); // 30 days from request
            $table->foreignId('assigned_to')->nullable()->constrained('users');
            $table->timestamp('completed_at')->nullable();
            $table->text('completion_notes')->nullable();
            $table->string('export_file_path')->nullable();
            $table->json('metadata')->nullable(); // Additional request metadata
            $table->string('requester_ip')->nullable();
            $table->string('requester_user_agent')->nullable();
            $table->timestamps();

            $table->index(['email', 'status']);
            $table->index(['status', 'due_date']);
            $table->index('user_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('data_subject_requests');
    }
};
