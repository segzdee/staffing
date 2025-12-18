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
        Schema::create('payroll_deductions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('payroll_item_id')->constrained()->onDelete('cascade');
            $table->enum('type', ['platform_fee', 'tax', 'garnishment', 'advance_repayment', 'uniform', 'other'])->default('other');
            $table->text('description');
            $table->decimal('amount', 10, 2);
            $table->boolean('is_percentage')->default(false);
            $table->decimal('percentage_rate', 5, 2)->nullable();
            $table->timestamps();

            // Index for queries
            $table->index('payroll_item_id');
            $table->index('type');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('payroll_deductions');
    }
};
