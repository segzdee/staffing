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
        Schema::create('fraud_rules', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('code')->unique();
            $table->text('description')->nullable();
            $table->enum('category', ['velocity', 'device', 'location', 'behavior', 'identity', 'payment']);
            $table->json('conditions'); // {field: 'signup_count', operator: '>', value: 3, period: '24h'}
            $table->integer('severity')->default(5);
            $table->enum('action', ['flag', 'block', 'review', 'notify'])->default('flag');
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            // Indexes
            $table->index('category');
            $table->index('is_active');
            $table->index('severity');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('fraud_rules');
    }
};
