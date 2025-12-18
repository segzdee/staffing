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
        Schema::create('loyalty_rewards', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->text('description')->nullable();
            $table->integer('points_required');
            $table->enum('type', ['cash_bonus', 'fee_discount', 'priority_matching', 'badge', 'merch']);
            $table->json('reward_data')->nullable(); // {amount: 10, discount_percent: 5, etc.}
            $table->integer('quantity_available')->nullable(); // null = unlimited
            $table->integer('quantity_redeemed')->default(0);
            $table->boolean('is_active')->default(true);
            $table->enum('min_tier', ['bronze', 'silver', 'gold', 'platinum'])->default('bronze');
            $table->timestamps();

            // Indexes
            $table->index('is_active');
            $table->index('type');
            $table->index('min_tier');
            $table->index('points_required');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('loyalty_rewards');
    }
};
