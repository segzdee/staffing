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
        Schema::table('agency_profiles', function (Blueprint $table) {
            $table->foreignId('agency_tier_id')->nullable()->after('user_id')->constrained('agency_tiers')->nullOnDelete();
            $table->timestamp('tier_achieved_at')->nullable()->after('agency_tier_id');
            $table->timestamp('tier_review_at')->nullable()->after('tier_achieved_at');
            $table->json('tier_metrics_snapshot')->nullable()->after('tier_review_at');

            $table->index('agency_tier_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('agency_profiles', function (Blueprint $table) {
            $table->dropForeign(['agency_tier_id']);
            $table->dropColumn([
                'agency_tier_id',
                'tier_achieved_at',
                'tier_review_at',
                'tier_metrics_snapshot',
            ]);
        });
    }
};
