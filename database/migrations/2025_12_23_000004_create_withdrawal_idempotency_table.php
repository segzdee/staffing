<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     * 
     * PRIORITY-0: Withdrawal idempotency table
     * Prevents duplicate withdrawal processing
     */
    public function up(): void
    {
        Schema::create('withdrawal_idempotency', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->onDelete('cascade');
            $table->string('idempotency_key', 255)->unique(); // Unique key per withdrawal request
            $table->foreignId('withdrawal_id')->nullable()->constrained('withdrawals')->onDelete('set null');
            $table->enum('status', ['pending', 'processing', 'completed', 'failed'])->default('pending');
            $table->text('request_data')->nullable(); // Store request data for replay
            $table->text('response_data')->nullable(); // Store response data
            $table->text('error_message')->nullable();
            $table->timestamp('processed_at')->nullable();
            $table->timestamps();

            // Indexes
            $table->index(['user_id', 'status']);
            $table->index('idempotency_key');
            $table->index('created_at'); // For cleanup
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('withdrawal_idempotency');
    }
};
