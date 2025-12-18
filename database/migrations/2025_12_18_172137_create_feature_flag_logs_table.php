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
        Schema::create('feature_flag_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('feature_flag_id')->constrained()->onDelete('cascade');
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->string('action'); // 'enabled', 'disabled', 'rollout_changed', 'created', 'updated', 'deleted'
            $table->json('old_value')->nullable();
            $table->json('new_value')->nullable();
            $table->timestamps();

            $table->index('feature_flag_id');
            $table->index('user_id');
            $table->index('action');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('feature_flag_logs');
    }
};
