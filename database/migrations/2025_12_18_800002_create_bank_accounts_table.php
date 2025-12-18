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
        Schema::create('bank_accounts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->string('account_holder_name');
            $table->string('bank_name')->nullable();
            $table->string('country_code', 2);
            $table->string('currency_code', 3);
            $table->string('iban')->nullable();
            $table->string('account_number')->nullable();
            $table->string('routing_number')->nullable(); // ACH
            $table->string('sort_code')->nullable(); // UK
            $table->string('bsb_code')->nullable(); // Australia
            $table->string('swift_bic')->nullable();
            $table->enum('account_type', ['checking', 'savings'])->default('checking');
            $table->boolean('is_verified')->default(false);
            $table->boolean('is_primary')->default(false);
            $table->json('metadata')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['user_id', 'is_primary']);
            $table->index('country_code');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('bank_accounts');
    }
};
