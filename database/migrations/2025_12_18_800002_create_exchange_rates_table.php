<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     * GLO-001: Multi-Currency Support - Exchange Rates
     */
    public function up(): void
    {
        Schema::create('exchange_rates', function (Blueprint $table) {
            $table->id();
            $table->string('base_currency', 3);
            $table->string('target_currency', 3);
            $table->decimal('rate', 15, 8);
            $table->decimal('inverse_rate', 15, 8);
            $table->string('source')->default('ecb'); // ecb, openexchangerates
            $table->timestamp('rate_date');
            $table->timestamps();

            $table->unique(['base_currency', 'target_currency', 'rate_date']);
            $table->index(['base_currency', 'target_currency']);
            $table->index('rate_date');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('exchange_rates');
    }
};
