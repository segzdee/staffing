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
        Schema::create('white_label_domains', function (Blueprint $table) {
            $table->id();
            $table->foreignId('white_label_config_id')->constrained()->onDelete('cascade');
            $table->string('domain');
            $table->string('verification_token');
            $table->enum('verification_method', ['dns_txt', 'dns_cname', 'file'])->default('dns_txt');
            $table->boolean('is_verified')->default(false);
            $table->timestamp('verified_at')->nullable();
            $table->timestamp('last_check_at')->nullable();
            $table->timestamps();

            $table->index('domain');
            $table->index('is_verified');
            $table->index('verification_token');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('white_label_domains');
    }
};
