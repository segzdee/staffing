<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * Makes certification_id nullable to support the new safety certification system
     * where worker certifications can reference safety_certification_id instead.
     */
    public function up(): void
    {
        Schema::table('worker_certifications', function (Blueprint $table) {
            // Drop the existing foreign key constraint first
            $table->dropForeign(['certification_id']);

            // Make the column nullable
            $table->unsignedBigInteger('certification_id')->nullable()->change();

            // Re-add the foreign key with ON DELETE SET NULL
            $table->foreign('certification_id')
                ->references('id')
                ->on('certifications')
                ->onDelete('set null');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('worker_certifications', function (Blueprint $table) {
            // Drop the modified foreign key
            $table->dropForeign(['certification_id']);

            // Make the column NOT NULL again (set existing nulls to 0 first if needed)
            $table->unsignedBigInteger('certification_id')->nullable(false)->change();

            // Re-add the original foreign key with ON DELETE CASCADE
            $table->foreign('certification_id')
                ->references('id')
                ->on('certifications')
                ->onDelete('cascade');
        });
    }
};
