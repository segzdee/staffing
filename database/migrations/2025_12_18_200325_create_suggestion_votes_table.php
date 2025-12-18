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
        Schema::create('suggestion_votes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('suggestion_id')->constrained('improvement_suggestions')->onDelete('cascade');
            $table->foreignId('user_id')->constrained();
            $table->enum('vote_type', ['up', 'down'])->default('up');
            $table->timestamps();

            $table->unique(['suggestion_id', 'user_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('suggestion_votes');
    }
};
