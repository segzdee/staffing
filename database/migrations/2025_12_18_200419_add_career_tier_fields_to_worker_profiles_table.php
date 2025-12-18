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
        Schema::table('worker_profiles', function (Blueprint $table) {
            $table->foreignId('worker_tier_id')->nullable()->after('user_id')->constrained('worker_tiers')->nullOnDelete();
            $table->timestamp('tier_achieved_at')->nullable()->after('worker_tier_id');
            $table->json('tier_progress')->nullable()->after('tier_achieved_at');
            $table->integer('lifetime_shifts')->default(0)->after('tier_progress');
            $table->decimal('lifetime_hours', 10, 2)->default(0)->after('lifetime_shifts');

            $table->index('worker_tier_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('worker_profiles', function (Blueprint $table) {
            $table->dropForeign(['worker_tier_id']);
            $table->dropIndex(['worker_tier_id']);
            $table->dropColumn([
                'worker_tier_id',
                'tier_achieved_at',
                'tier_progress',
                'lifetime_shifts',
                'lifetime_hours',
            ]);
        });
    }
};
