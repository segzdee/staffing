<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     * GLO-002: Add tax tracking fields to shift_payments table
     */
    public function up(): void
    {
        Schema::table('shift_payments', function (Blueprint $table) {
            // Add tax calculation reference
            $table->foreignId('tax_calculation_id')
                ->nullable()
                ->after('status')
                ->constrained('tax_calculations')
                ->nullOnDelete();

            // Add tax withheld amount
            $table->decimal('tax_withheld', 10, 2)
                ->default(0)
                ->after('tax_calculation_id')
                ->comment('Total tax withheld from payment');

            // Index for tax reporting queries
            $table->index('tax_calculation_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('shift_payments', function (Blueprint $table) {
            $table->dropForeign(['tax_calculation_id']);
            $table->dropColumn(['tax_calculation_id', 'tax_withheld']);
        });
    }
};
