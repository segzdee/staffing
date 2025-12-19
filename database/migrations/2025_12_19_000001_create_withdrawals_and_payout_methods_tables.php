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
        if (!Schema::hasTable('tax_documents')) {
            Schema::create('tax_documents', function (Blueprint $table) {
                $table->id();
                $table->foreignId('user_id')->constrained()->onDelete('cascade');
                $table->string('type'); // '1099', 'W-2', etc.
                $table->year('tax_year');
                $table->string('document_url')->nullable();
                $table->string('status')->default('pending'); // pending, available, downloaded
                $table->decimal('total_earnings', 12, 2)->default(0);
                $table->timestamp('generated_at')->nullable();
                $table->timestamp('downloaded_at')->nullable();
                $table->timestamps();

                $table->index(['user_id', 'tax_year']);
            });
        }

        if (!Schema::hasTable('payout_methods')) {
            Schema::create('payout_methods', function (Blueprint $table) {
                $table->id();
                $table->foreignId('user_id')->constrained()->onDelete('cascade');
                $table->string('type'); // 'bank_account', 'debit_card', 'paypal', etc.
                $table->string('provider')->nullable(); // 'stripe', 'paypal', etc.
                $table->string('external_id')->nullable(); // External reference ID
                $table->string('label')->nullable(); // User-friendly label
                $table->string('last_four')->nullable(); // Last 4 digits
                $table->string('bank_name')->nullable();
                $table->string('account_type')->nullable(); // 'checking', 'savings'
                $table->boolean('is_active')->default(true);
                $table->boolean('is_default')->default(false);
                $table->boolean('is_verified')->default(false);
                $table->json('metadata')->nullable();
                $table->timestamps();

                $table->index(['user_id', 'is_active']);
            });
        }

        if (!Schema::hasTable('favourite_workers')) {
            Schema::create('favourite_workers', function (Blueprint $table) {
                $table->id();
                $table->foreignId('business_id')->constrained('users')->onDelete('cascade');
                $table->foreignId('worker_id')->constrained('users')->onDelete('cascade');
                $table->text('notes')->nullable();
                $table->timestamps();

                $table->unique(['business_id', 'worker_id']);
                $table->index('business_id');
            });
        }

        if (!Schema::hasTable('blocked_workers')) {
            Schema::create('blocked_workers', function (Blueprint $table) {
                $table->id();
                $table->foreignId('business_id')->constrained('users')->onDelete('cascade');
                $table->foreignId('worker_id')->constrained('users')->onDelete('cascade');
                $table->text('reason')->nullable();
                $table->foreignId('blocked_by')->nullable()->constrained('users')->nullOnDelete();
                $table->timestamps();

                $table->unique(['business_id', 'worker_id']);
                $table->index('business_id');
            });
        }

        if (!Schema::hasTable('withdrawals')) {
            Schema::create('withdrawals', function (Blueprint $table) {
                $table->id();
                $table->foreignId('user_id')->constrained()->onDelete('cascade');
                $table->foreignId('payout_method_id')->nullable()->constrained('payout_methods')->nullOnDelete();
                $table->bigInteger('amount'); // In cents
                $table->string('currency', 3)->default('USD');
                $table->enum('status', ['pending', 'processing', 'completed', 'failed', 'cancelled'])->default('pending');
                $table->string('external_payout_id')->nullable(); // Stripe payout ID
                $table->string('failure_reason')->nullable();
                $table->timestamp('processed_at')->nullable();
                $table->timestamp('completed_at')->nullable();
                $table->json('metadata')->nullable();
                $table->timestamps();

                $table->index(['user_id', 'status']);
                $table->index('external_payout_id');
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('withdrawals');
        Schema::dropIfExists('payout_methods');
        Schema::dropIfExists('tax_documents');
        Schema::dropIfExists('favourite_workers');
        Schema::dropIfExists('blocked_workers');
    }
};
