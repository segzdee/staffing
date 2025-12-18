<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * WKR-007: Worker Career Tiers System
     */
    public function up(): void
    {
        Schema::create('worker_tiers', function (Blueprint $table) {
            $table->id();
            $table->string('name'); // Rookie, Regular, Pro, Elite, Legend
            $table->string('slug')->unique();
            $table->integer('level'); // 1-5
            $table->integer('min_shifts_completed')->default(0);
            $table->decimal('min_rating', 3, 2)->default(0);
            $table->integer('min_hours_worked')->default(0);
            $table->integer('min_months_active')->default(0);
            $table->decimal('fee_discount_percent', 5, 2)->default(0);
            $table->integer('priority_booking_hours')->default(0);
            $table->boolean('instant_payout')->default(false);
            $table->boolean('premium_shifts_access')->default(false);
            $table->json('additional_benefits')->nullable();
            $table->string('badge_color')->default('#6B7280');
            $table->string('badge_icon')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->index('level');
            $table->index('is_active');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('worker_tiers');
    }
};
