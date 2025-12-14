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
        Schema::create('tax_rates', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('country', 10);
            $table->string('iso_state', 10)->nullable();
            $table->decimal('percentage', 8, 4);
            $table->string('status', 1)->default('1'); // '1' = active
            $table->string('type')->default('vat'); // vat, sales_tax, etc.
            $table->timestamps();

            $table->index(['country', 'iso_state', 'status']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('tax_rates');
    }
};
