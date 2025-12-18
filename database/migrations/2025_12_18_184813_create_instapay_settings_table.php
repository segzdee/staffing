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
        Schema::create('instapay_settings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->boolean('enabled')->default(false);
            $table->string('preferred_method')->default('stripe'); // stripe, paypal, bank_transfer
            $table->decimal('minimum_amount', 10, 2)->default(10.00);
            $table->boolean('auto_request')->default(false); // auto-request after each shift
            $table->time('daily_cutoff')->default('14:00:00'); // cutoff for same-day processing
            $table->decimal('daily_limit_override', 10, 2)->nullable(); // custom daily limit (null = use default)
            $table->timestamps();

            // Unique constraint - one settings record per user
            $table->unique('user_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('instapay_settings');
    }
};
