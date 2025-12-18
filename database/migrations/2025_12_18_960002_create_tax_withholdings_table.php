<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     * FIN-007: Tax Reporting - Tax withholdings table for tracking per-shift tax deductions
     */
    public function up(): void
    {
        Schema::create('tax_withholdings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->foreignId('shift_id')->nullable()->constrained()->onDelete('set null');
            $table->foreignId('tax_jurisdiction_id')->constrained()->onDelete('cascade');
            $table->decimal('gross_amount', 10, 2);
            $table->decimal('federal_withholding', 10, 2)->default(0);
            $table->decimal('state_withholding', 10, 2)->default(0);
            $table->decimal('social_security', 10, 2)->default(0);
            $table->decimal('medicare', 10, 2)->default(0);
            $table->decimal('other_withholding', 10, 2)->default(0);
            $table->decimal('total_withheld', 10, 2)->default(0);
            $table->date('pay_period_start');
            $table->date('pay_period_end');
            $table->timestamps();

            $table->index(['user_id', 'pay_period_start', 'pay_period_end']);
            $table->index(['tax_jurisdiction_id']);
            $table->index(['shift_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('tax_withholdings');
    }
};
