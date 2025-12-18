<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * QUA-003: Feedback Loop System - Surveys Table
 *
 * Stores survey definitions for NPS, CSAT, post-shift, and general feedback surveys.
 */
return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('surveys', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('slug')->unique();
            $table->text('description')->nullable();
            $table->enum('type', ['nps', 'csat', 'post_shift', 'onboarding', 'general'])->default('general');
            $table->enum('target_audience', ['workers', 'businesses', 'all'])->default('all');
            $table->json('questions'); // [{id, type, text, options, required}]
            $table->boolean('is_active')->default(true);
            $table->timestamp('starts_at')->nullable();
            $table->timestamp('ends_at')->nullable();
            $table->timestamps();

            $table->index(['type', 'is_active']);
            $table->index(['target_audience', 'is_active']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('surveys');
    }
};
