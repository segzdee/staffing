<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * WKR-010: Worker Portfolio & Showcase Features
 * Creates worker_profile_views table for analytics tracking
 */
return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('worker_profile_views', function (Blueprint $table) {
            $table->id();
            $table->foreignId('worker_id')->constrained('users')->onDelete('cascade');
            $table->foreignId('viewer_id')->nullable()->constrained('users')->onDelete('set null');
            $table->enum('viewer_type', ['business', 'agency', 'worker', 'guest'])->default('guest');
            $table->enum('source', ['search', 'direct', 'public_profile', 'shift_application', 'referral', 'other'])->default('other');
            $table->string('ip_address', 45)->nullable();
            $table->string('user_agent')->nullable();
            $table->string('referrer_url')->nullable();
            $table->boolean('converted_to_application')->default(false);
            $table->timestamp('converted_at')->nullable();
            $table->timestamps();

            // Indexes for analytics queries
            $table->index(['worker_id', 'created_at']);
            $table->index(['worker_id', 'viewer_type']);
            $table->index(['worker_id', 'source']);
            $table->index(['worker_id', 'converted_to_application']);
        });

        // Add daily aggregated stats table for faster queries
        Schema::create('worker_profile_view_stats', function (Blueprint $table) {
            $table->id();
            $table->foreignId('worker_id')->constrained('users')->onDelete('cascade');
            $table->date('date');
            $table->unsignedInteger('total_views')->default(0);
            $table->unsignedInteger('unique_views')->default(0);
            $table->unsignedInteger('business_views')->default(0);
            $table->unsignedInteger('agency_views')->default(0);
            $table->unsignedInteger('conversions')->default(0);
            $table->timestamps();

            $table->unique(['worker_id', 'date']);
            $table->index(['worker_id', 'date']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('worker_profile_view_stats');
        Schema::dropIfExists('worker_profile_views');
    }
};
