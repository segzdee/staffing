<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * QUA-003: Feedback Loop System - Bug Reports Table
 *
 * Stores user-submitted bug reports with severity tracking and status management.
 */
return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('bug_reports', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->string('title');
            $table->text('description');
            $table->text('steps_to_reproduce')->nullable();
            $table->string('expected_behavior')->nullable();
            $table->string('actual_behavior')->nullable();
            $table->enum('severity', ['low', 'medium', 'high', 'critical'])->default('medium');
            $table->enum('status', ['reported', 'confirmed', 'in_progress', 'fixed', 'closed', 'wont_fix'])->default('reported');
            $table->json('attachments')->nullable();
            $table->string('browser')->nullable();
            $table->string('os')->nullable();
            $table->string('app_version')->nullable();
            $table->text('admin_notes')->nullable();
            $table->timestamps();

            $table->index(['status', 'severity']);
            $table->index(['user_id']);
            $table->index(['severity', 'status']);
            $table->index(['created_at']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('bug_reports');
    }
};
