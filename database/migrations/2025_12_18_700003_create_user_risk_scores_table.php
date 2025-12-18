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
        Schema::create('user_risk_scores', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->integer('risk_score')->default(0); // 0-100
            $table->enum('risk_level', ['low', 'medium', 'high', 'critical'])->default('low');
            $table->json('score_factors')->nullable();
            $table->timestamp('last_calculated_at')->nullable();
            $table->timestamps();

            // Indexes
            $table->unique('user_id');
            $table->index('risk_score');
            $table->index('risk_level');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('user_risk_scores');
    }
};
