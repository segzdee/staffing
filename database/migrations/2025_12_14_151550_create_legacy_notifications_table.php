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
        // Legacy notifications table (used by older Notifications model)
        Schema::create('notifications', function (Blueprint $table) {
            $table->id();
            $table->foreignId('destination')->constrained('users')->onDelete('cascade');
            $table->foreignId('author')->constrained('users')->onDelete('cascade');
            $table->integer('type');
            $table->unsignedBigInteger('target')->nullable();
            $table->boolean('read')->default(false);
            $table->timestamp('created_at')->useCurrent();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('notifications');
    }
};
