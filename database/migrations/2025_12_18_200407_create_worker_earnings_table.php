<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     * WKR-006: Earnings Dashboard - Worker Earnings Table
     */
    public function up(): void
    {
        Schema::create('worker_earnings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->foreignId('shift_id')->nullable()->constrained()->onDelete('set null');
            $table->enum('type', ['shift_pay', 'bonus', 'tip', 'referral', 'adjustment', 'reimbursement']);
            $table->decimal('gross_amount', 10, 2);
            $table->decimal('platform_fee', 10, 2)->default(0);
            $table->decimal('tax_withheld', 10, 2)->default(0);
            $table->decimal('net_amount', 10, 2);
            $table->string('currency', 3)->default('USD');
            $table->enum('status', ['pending', 'approved', 'paid', 'disputed'])->default('pending');
            $table->text('description')->nullable();
            $table->date('earned_date');
            $table->date('pay_date')->nullable();
            $table->timestamps();

            // Indexes for efficient querying
            $table->index(['user_id', 'earned_date']);
            $table->index(['user_id', 'status']);
            $table->index(['user_id', 'type']);
            $table->index('earned_date');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('worker_earnings');
    }
};
