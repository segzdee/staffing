<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * ADM-003: System Setting Audits Migration
 *
 * Creates the audit trail table for tracking all changes to system settings.
 */
return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('system_setting_audits', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('setting_id');
            $table->string('key');
            $table->text('old_value')->nullable();
            $table->text('new_value');
            $table->unsignedBigInteger('changed_by')->nullable();
            $table->string('ip_address', 45)->nullable();
            $table->text('user_agent')->nullable();
            $table->timestamp('created_at');

            // Indexes for efficient querying
            $table->index('setting_id');
            $table->index('key');
            $table->index('changed_by');
            $table->index('created_at');

            // Foreign keys
            $table->foreign('setting_id')
                ->references('id')
                ->on('system_settings')
                ->onDelete('cascade');

            $table->foreign('changed_by')
                ->references('id')
                ->on('users')
                ->onDelete('set null');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('system_setting_audits');
    }
};
