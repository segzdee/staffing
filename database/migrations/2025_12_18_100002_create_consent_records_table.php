<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     * GLO-005: GDPR/CCPA Compliance - Consent Records
     */
    public function up(): void
    {
        Schema::create('consent_records', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable()->constrained()->onDelete('cascade');
            $table->string('session_id')->nullable(); // for anonymous users
            $table->string('consent_type'); // marketing, analytics, functional, necessary
            $table->boolean('consented')->default(false);
            $table->string('ip_address')->nullable();
            $table->string('user_agent')->nullable();
            $table->json('consent_details')->nullable(); // Additional consent metadata
            $table->string('consent_version')->nullable(); // Version of consent policy
            $table->string('consent_source')->nullable(); // cookie_banner, registration, settings
            $table->timestamp('consented_at')->nullable();
            $table->timestamp('withdrawn_at')->nullable();
            $table->timestamps();

            $table->index(['user_id', 'consent_type']);
            $table->index(['session_id', 'consent_type']);
            $table->index('consent_type');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('consent_records');
    }
};
