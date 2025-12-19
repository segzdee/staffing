<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     * 
     * Makes the author field nullable in notifications table to support
     * system-generated notifications that don't have an author.
     */
    public function up(): void
    {
        Schema::table('notifications', function (Blueprint $table) {
            // Drop the foreign key constraint first
            $table->dropForeign(['author']);
            
            // Make the column nullable
            $table->foreignId('author')->nullable()->change();
            
            // Re-add the foreign key constraint with nullable support
            $table->foreign('author')->references('id')->on('users')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('notifications', function (Blueprint $table) {
            // Drop the foreign key constraint
            $table->dropForeign(['author']);
            
            // Make the column non-nullable again
            $table->foreignId('author')->nullable(false)->change();
            
            // Re-add the foreign key constraint
            $table->foreign('author')->references('id')->on('users')->onDelete('cascade');
        });
    }
};
