<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     * GLO-005: GDPR/CCPA Compliance - Data Retention Policies
     */
    public function up(): void
    {
        Schema::create('data_retention_policies', function (Blueprint $table) {
            $table->id();
            $table->string('data_type'); // messages, shifts, payments, logs
            $table->string('model_class');
            $table->integer('retention_days');
            $table->enum('action', ['delete', 'anonymize', 'archive']);
            $table->boolean('is_active')->default(true);
            $table->text('description')->nullable();
            $table->json('conditions')->nullable(); // Additional conditions for retention
            $table->timestamp('last_executed_at')->nullable();
            $table->integer('last_affected_count')->default(0);
            $table->timestamps();

            $table->index(['data_type', 'is_active']);
            $table->unique(['data_type', 'model_class']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('data_retention_policies');
    }
};
