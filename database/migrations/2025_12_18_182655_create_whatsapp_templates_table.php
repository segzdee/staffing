<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * COM-004: WhatsApp Business API Integration
     * Stores WhatsApp message templates approved by Meta.
     */
    public function up(): void
    {
        Schema::create('whatsapp_templates', function (Blueprint $table) {
            $table->id();
            $table->string('name')->index();
            $table->string('template_id')->unique()->comment('WhatsApp template ID from Meta');
            $table->string('language', 10)->default('en');
            $table->enum('category', ['utility', 'marketing', 'authentication']);
            $table->text('content')->comment('Template content with {{1}} placeholders');
            $table->json('header')->nullable()->comment('Header config: {type: text|image|document, content: ...}');
            $table->json('buttons')->nullable()->comment('Button configurations');
            $table->json('footer')->nullable()->comment('Footer text');
            $table->enum('status', ['pending', 'approved', 'rejected'])->default('pending');
            $table->boolean('is_active')->default(true);
            $table->text('rejection_reason')->nullable();
            $table->timestamp('approved_at')->nullable();
            $table->timestamp('last_synced_at')->nullable();
            $table->timestamps();

            $table->index(['category', 'status', 'is_active']);
            $table->index(['language', 'is_active']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('whatsapp_templates');
    }
};
