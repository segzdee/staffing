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
        Schema::create('improvement_metrics', function (Blueprint $table) {
            $table->id();
            $table->string('metric_key')->unique();
            $table->string('name');
            $table->text('description')->nullable();
            $table->decimal('current_value', 15, 4)->default(0);
            $table->decimal('target_value', 15, 4)->nullable();
            $table->decimal('baseline_value', 15, 4)->nullable();
            $table->enum('trend', ['up', 'down', 'stable'])->default('stable');
            $table->string('unit')->nullable();
            $table->json('history')->nullable();
            $table->timestamp('measured_at')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('improvement_metrics');
    }
};
