<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     * COM-001: Enhance conversations table for multi-participant support
     */
    public function up(): void
    {
        Schema::table('conversations', function (Blueprint $table) {
            // Add conversation type
            $table->enum('type', ['direct', 'shift', 'support', 'broadcast'])
                ->default('direct')
                ->after('id');

            // Add archive flag (different from status = archived)
            $table->boolean('is_archived')->default(false)->after('status');

            // Add indexes for new columns
            $table->index('type');
            $table->index('is_archived');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('conversations', function (Blueprint $table) {
            $table->dropIndex(['type']);
            $table->dropIndex(['is_archived']);
            $table->dropColumn(['type', 'is_archived']);
        });
    }
};
