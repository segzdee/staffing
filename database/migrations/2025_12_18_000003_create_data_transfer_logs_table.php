<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * GLO-010: Data Residency System
 *
 * Creates the data_transfer_logs table for auditing all cross-region
 * data transfers for compliance purposes.
 */
return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('data_transfer_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained();
            $table->string('from_region');
            $table->string('to_region');
            $table->enum('transfer_type', ['migration', 'backup', 'export', 'processing']);
            $table->enum('status', ['pending', 'in_progress', 'completed', 'failed']);
            $table->json('data_types'); // What data was transferred
            $table->text('legal_basis')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->text('error_message')->nullable();
            $table->json('metadata')->nullable();
            $table->timestamps();

            $table->index('user_id');
            $table->index('from_region');
            $table->index('to_region');
            $table->index('status');
            $table->index('transfer_type');
            $table->index('created_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('data_transfer_logs');
    }
};
