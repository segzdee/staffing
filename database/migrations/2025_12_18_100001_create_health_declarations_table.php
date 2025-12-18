<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     * SAF-005: COVID/Health Protocols - Health Declarations Table
     */
    public function up(): void
    {
        Schema::create('health_declarations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->foreignId('shift_id')->nullable()->constrained()->onDelete('set null');
            $table->boolean('fever_free')->default(true);
            $table->boolean('no_symptoms')->default(true);
            $table->boolean('no_exposure')->default(true);
            $table->boolean('fit_for_work')->default(true);
            $table->timestamp('declared_at');
            $table->string('ip_address')->nullable();
            $table->timestamps();

            // Indexes for common queries
            $table->index(['user_id', 'declared_at']);
            $table->index(['shift_id', 'declared_at']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('health_declarations');
    }
};
