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
        Schema::create('cross_border_transfers', function (Blueprint $table) {
            $table->id();
            $table->string('transfer_reference')->unique();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->foreignId('bank_account_id')->constrained()->onDelete('cascade');
            $table->string('source_currency', 3);
            $table->string('destination_currency', 3);
            $table->decimal('source_amount', 15, 2);
            $table->decimal('destination_amount', 15, 2);
            $table->decimal('exchange_rate', 15, 8);
            $table->decimal('fee_amount', 10, 2)->default(0);
            $table->enum('payment_method', ['sepa', 'swift', 'ach', 'faster_payments', 'local']);
            $table->enum('status', ['pending', 'processing', 'sent', 'completed', 'failed', 'returned'])->default('pending');
            $table->timestamp('estimated_arrival_at')->nullable();
            $table->timestamp('sent_at')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->string('provider_reference')->nullable();
            $table->text('failure_reason')->nullable();
            $table->timestamps();

            $table->index(['user_id', 'status']);
            $table->index('transfer_reference');
            $table->index('status');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('cross_border_transfers');
    }
};
