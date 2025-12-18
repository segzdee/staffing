<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * FIN-011: Subscription Billing System
 *
 * Creates the subscription_plans table to define available subscription
 * tiers for workers, businesses, and agencies.
 */
return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('subscription_plans', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('slug')->unique();
            $table->enum('type', ['worker', 'business', 'agency']);
            $table->enum('interval', ['monthly', 'quarterly', 'yearly']);
            $table->decimal('price', 10, 2);
            $table->string('currency', 3)->default('USD');
            $table->string('stripe_price_id')->nullable();
            $table->string('stripe_product_id')->nullable();
            $table->json('features');
            $table->text('description')->nullable();
            $table->integer('trial_days')->default(0);
            $table->boolean('is_popular')->default(false);
            $table->boolean('is_active')->default(true);
            $table->integer('sort_order')->default(0);
            $table->integer('max_users')->nullable()->comment('For business/agency plans: max team members');
            $table->integer('max_shifts_per_month')->nullable()->comment('For business plans: shift posting limit');
            $table->decimal('commission_rate', 5, 2)->nullable()->comment('Platform commission percentage');
            $table->timestamps();

            // Indexes
            $table->index(['type', 'is_active']);
            $table->index('stripe_price_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('subscription_plans');
    }
};
