<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     * WKR-006: Add hours tracking and dispute columns to worker_earnings
     */
    public function up(): void
    {
        Schema::table('worker_earnings', function (Blueprint $table) {
            $table->decimal('hours_worked', 5, 2)->nullable()->after('net_amount');
            $table->decimal('hourly_rate', 10, 2)->nullable()->after('hours_worked');
            $table->timestamp('paid_at')->nullable()->after('pay_date');
            $table->text('dispute_reason')->nullable()->after('status');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('worker_earnings', function (Blueprint $table) {
            $table->dropColumn(['hours_worked', 'hourly_rate', 'paid_at', 'dispute_reason']);
        });
    }
};
