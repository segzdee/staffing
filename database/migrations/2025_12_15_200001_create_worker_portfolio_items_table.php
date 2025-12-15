<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * WKR-010: Worker Portfolio & Showcase Features
 * Creates worker_portfolio_items table for storing portfolio media
 */
return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('worker_portfolio_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('worker_id')->constrained('users')->onDelete('cascade');
            $table->enum('type', ['photo', 'video', 'document', 'certification'])->default('photo');
            $table->string('title', 100);
            $table->text('description')->nullable();
            $table->string('file_path');
            $table->string('thumbnail_path')->nullable();
            $table->string('original_filename')->nullable();
            $table->string('mime_type', 100)->nullable();
            $table->unsignedBigInteger('file_size')->default(0); // in bytes
            $table->unsignedTinyInteger('display_order')->default(0);
            $table->boolean('is_featured')->default(false);
            $table->boolean('is_visible')->default(true);
            $table->json('metadata')->nullable(); // For video duration, dimensions, etc.
            $table->timestamps();
            $table->softDeletes();

            // Indexes
            $table->index(['worker_id', 'display_order']);
            $table->index(['worker_id', 'is_featured']);
            $table->index(['worker_id', 'type']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('worker_portfolio_items');
    }
};
