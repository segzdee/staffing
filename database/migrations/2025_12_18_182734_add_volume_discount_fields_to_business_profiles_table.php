<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * FIN-001: Add Volume Discount Fields to Business Profiles
 *
 * Adds fields to track current volume tier and lifetime metrics
 * for businesses. Also supports custom pricing for enterprise clients.
 */
return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('business_profiles', function (Blueprint $table) {
            // Current volume tier
            $table->foreignId('current_volume_tier_id')
                ->nullable()
                ->after('subscription_plan')
                ->constrained('volume_discount_tiers')
                ->onDelete('set null');

            // Lifetime metrics
            $table->integer('lifetime_shifts')->default(0)->after('current_volume_tier_id');
            $table->decimal('lifetime_spend', 15, 2)->default(0)->after('lifetime_shifts');
            $table->decimal('lifetime_savings', 12, 2)->default(0)->after('lifetime_spend');

            // Custom pricing override (for enterprise contracts)
            $table->boolean('custom_pricing')->default(false)->after('lifetime_savings');
            $table->decimal('custom_fee_percent', 5, 2)->nullable()->after('custom_pricing');
            $table->text('custom_pricing_notes')->nullable()->after('custom_fee_percent');
            $table->date('custom_pricing_expires_at')->nullable()->after('custom_pricing_notes');

            // Tier tracking
            $table->timestamp('tier_upgraded_at')->nullable()->after('custom_pricing_expires_at');
            $table->timestamp('tier_downgraded_at')->nullable()->after('tier_upgraded_at');
            $table->integer('months_at_current_tier')->default(0)->after('tier_downgraded_at');

            // Index for tier queries
            $table->index('current_volume_tier_id');
            $table->index('custom_pricing');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('business_profiles', function (Blueprint $table) {
            $table->dropForeign(['current_volume_tier_id']);
            $table->dropIndex(['current_volume_tier_id']);
            $table->dropIndex(['custom_pricing']);

            $table->dropColumn([
                'current_volume_tier_id',
                'lifetime_shifts',
                'lifetime_spend',
                'lifetime_savings',
                'custom_pricing',
                'custom_fee_percent',
                'custom_pricing_notes',
                'custom_pricing_expires_at',
                'tier_upgraded_at',
                'tier_downgraded_at',
                'months_at_current_tier',
            ]);
        });
    }
};
