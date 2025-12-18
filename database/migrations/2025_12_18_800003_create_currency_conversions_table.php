<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     * GLO-001: Multi-Currency Support - Currency Conversions (Audit Trail)
     */
    public function up(): void
    {
        Schema::create('currency_conversions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->string('from_currency', 3);
            $table->string('to_currency', 3);
            $table->decimal('from_amount', 15, 2);
            $table->decimal('to_amount', 15, 2);
            $table->decimal('exchange_rate', 15, 8);
            $table->decimal('fee_amount', 15, 2)->default(0);
            $table->string('reference_type')->nullable(); // shift_payment, withdrawal, etc.
            $table->unsignedBigInteger('reference_id')->nullable();
            $table->string('status')->default('completed'); // pending, completed, failed
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->index(['user_id', 'created_at']);
            $table->index(['from_currency', 'to_currency']);
            $table->index(['reference_type', 'reference_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('currency_conversions');
    }
};
