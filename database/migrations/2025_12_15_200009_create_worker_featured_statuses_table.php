<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * WKR-010: Worker Portfolio & Showcase Features
 * Creates worker_featured_statuses table for featured profile promotions
 */
return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('worker_featured_statuses', function (Blueprint $table) {
            $table->id();
            $table->foreignId('worker_id')->constrained('users')->onDelete('cascade');
            $table->enum('tier', ['bronze', 'silver', 'gold'])->default('bronze');
            $table->dateTime('start_date');
            $table->dateTime('end_date');
            $table->unsignedInteger('cost_cents'); // Cost in cents (smallest currency unit)
            $table->char('currency', 3)->default('EUR');
            $table->string('payment_reference')->nullable(); // External payment ID
            $table->enum('status', ['pending', 'active', 'expired', 'cancelled', 'refunded'])->default('pending');
            $table->text('notes')->nullable();
            $table->timestamps();

            // Indexes
            $table->index(['worker_id', 'status']);
            $table->index(['start_date', 'end_date']);
            $table->index('status');
        });

        // Add public profile settings to worker_profiles table
        Schema::table('worker_profiles', function (Blueprint $table) {
            $table->boolean('public_profile_enabled')->default(false)->after('linkedin_url');
            $table->string('public_profile_slug')->nullable()->unique()->after('public_profile_enabled');
            $table->timestamp('public_profile_enabled_at')->nullable()->after('public_profile_slug');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('worker_featured_statuses');

        Schema::table('worker_profiles', function (Blueprint $table) {
            $table->dropColumn(['public_profile_enabled', 'public_profile_slug', 'public_profile_enabled_at']);
        });
    }
};
