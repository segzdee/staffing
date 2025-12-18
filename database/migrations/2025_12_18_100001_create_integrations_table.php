<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     * BIZ-012: Integration APIs - External Integration System
     */
    public function up(): void
    {
        Schema::create('integrations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('business_id')->constrained('users')->onDelete('cascade');
            $table->string('provider'); // deputy, when_i_work, gusto, adp, google_calendar, outlook
            $table->string('name');
            $table->enum('type', ['hr', 'scheduling', 'payroll', 'pos', 'calendar', 'accounting']);
            $table->json('credentials')->nullable(); // encrypted
            $table->json('settings')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamp('connected_at')->nullable();
            $table->timestamp('last_sync_at')->nullable();
            $table->integer('sync_errors')->default(0);
            $table->timestamps();

            $table->unique(['business_id', 'provider']);
            $table->index(['business_id', 'is_active']);
            $table->index(['provider', 'is_active']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('integrations');
    }
};
