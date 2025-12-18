<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('agency_tiers', function (Blueprint $table) {
            $table->id();
            $table->string('name'); // Bronze, Silver, Gold, Platinum, Diamond
            $table->string('slug')->unique();
            $table->integer('level'); // 1-5
            $table->decimal('min_monthly_revenue', 12, 2)->default(0);
            $table->integer('min_active_workers')->default(0);
            $table->decimal('min_fill_rate', 5, 2)->default(0);
            $table->decimal('min_rating', 3, 2)->default(0);
            $table->decimal('commission_rate', 5, 2); // Lower for higher tiers
            $table->integer('priority_booking_hours')->default(0);
            $table->boolean('dedicated_support')->default(false);
            $table->boolean('custom_branding')->default(false);
            $table->boolean('api_access')->default(false);
            $table->json('additional_benefits')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->index(['level', 'is_active']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('agency_tiers');
    }
};
