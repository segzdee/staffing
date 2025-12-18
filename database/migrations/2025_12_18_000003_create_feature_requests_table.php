<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * QUA-003: Feedback Loop System - Feature Requests Table
 *
 * Stores user-submitted feature requests with voting and status tracking.
 */
return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('feature_requests', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->string('title');
            $table->text('description');
            $table->enum('category', ['ui', 'feature', 'integration', 'mobile', 'other'])->default('feature');
            $table->enum('status', ['submitted', 'under_review', 'planned', 'in_progress', 'completed', 'declined'])->default('submitted');
            $table->integer('vote_count')->default(0);
            $table->integer('priority')->nullable();
            $table->text('admin_notes')->nullable();
            $table->timestamps();

            $table->index(['status', 'vote_count']);
            $table->index(['category', 'status']);
            $table->index(['user_id']);
            $table->index(['vote_count']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('feature_requests');
    }
};
