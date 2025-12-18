<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     * GLO-002: Tax Jurisdiction Engine - Audit trail for all tax calculations
     */
    public function up(): void
    {
        Schema::create('tax_calculations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->foreignId('shift_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('shift_payment_id')->nullable()->constrained('shift_payments')->nullOnDelete();
            $table->foreignId('tax_jurisdiction_id')->constrained()->onDelete('cascade');
            $table->decimal('gross_amount', 10, 2)->comment('Gross earnings amount');
            $table->decimal('income_tax', 10, 2)->default(0)->comment('Income tax withheld');
            $table->decimal('social_security', 10, 2)->default(0)->comment('Social security contribution');
            $table->decimal('vat_amount', 10, 2)->default(0)->comment('VAT amount');
            $table->decimal('withholding', 10, 2)->default(0)->comment('Withholding tax');
            $table->decimal('net_amount', 10, 2)->comment('Net amount after all deductions');
            $table->json('breakdown')->nullable()->comment('Detailed calculation breakdown');
            $table->decimal('effective_tax_rate', 5, 2)->default(0)->comment('Effective tax rate applied');
            $table->string('currency_code', 3)->default('USD');
            $table->enum('calculation_type', [
                'shift_payment',    // Regular shift payment
                'bonus',            // Bonus payment
                'adjustment',       // Payment adjustment
                'refund',           // Refund calculation
                'estimate',         // Tax estimate (not applied)
            ])->default('shift_payment');
            $table->boolean('is_applied')->default(true)->comment('Whether this calculation was applied');
            $table->timestamps();

            // Indexes
            $table->index(['user_id', 'created_at']);
            $table->index(['shift_id']);
            $table->index(['tax_jurisdiction_id']);
            $table->index(['calculation_type']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('tax_calculations');
    }
};
