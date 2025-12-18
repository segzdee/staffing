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
        Schema::create('payment_corridors', function (Blueprint $table) {
            $table->id();
            $table->string('source_country', 2); // ISO country code
            $table->string('destination_country', 2);
            $table->string('source_currency', 3);
            $table->string('destination_currency', 3);
            $table->enum('payment_method', ['sepa', 'swift', 'ach', 'faster_payments', 'local']);
            $table->integer('estimated_days_min')->default(1);
            $table->integer('estimated_days_max')->default(5);
            $table->decimal('fee_fixed', 10, 2)->default(0);
            $table->decimal('fee_percent', 5, 2)->default(0);
            $table->decimal('min_amount', 15, 2)->nullable();
            $table->decimal('max_amount', 15, 2)->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->unique(['source_country', 'destination_country', 'payment_method'], 'payment_corridors_unique');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('payment_corridors');
    }
};
