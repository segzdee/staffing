<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * AGY-003: Add payout tracking fields to agency_workers table
 *
 * This migration adds fields to track individual payout transactions
 * for each agency-worker relationship. Allows tracking of commission
 * payouts at the worker level.
 */
return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('agency_workers', function (Blueprint $table) {
            // Payout transaction tracking
            $table->string('last_payout_transaction_id')->nullable()->after('removed_at');
            $table->timestamp('last_payout_at')->nullable()->after('last_payout_transaction_id');
            $table->decimal('last_payout_amount', 10, 2)->nullable()->after('last_payout_at');

            // Aggregated payout statistics
            $table->decimal('total_commission_earned', 12, 2)->default(0.00)->after('last_payout_amount');
            $table->decimal('total_commission_paid', 12, 2)->default(0.00)->after('total_commission_earned');
            $table->decimal('pending_commission', 10, 2)->default(0.00)->after('total_commission_paid');
            $table->integer('payout_count')->default(0)->after('pending_commission');

            // Indexes for efficient querying
            $table->index('last_payout_transaction_id');
            $table->index('pending_commission');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('agency_workers', function (Blueprint $table) {
            // Drop indexes first
            $table->dropIndex(['last_payout_transaction_id']);
            $table->dropIndex(['pending_commission']);

            // Drop columns
            $table->dropColumn([
                'last_payout_transaction_id',
                'last_payout_at',
                'last_payout_amount',
                'total_commission_earned',
                'total_commission_paid',
                'pending_commission',
                'payout_count',
            ]);
        });
    }
};
