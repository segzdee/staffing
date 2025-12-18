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
        Schema::create('emergency_contacts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->string('name');
            $table->string('relationship'); // spouse, parent, sibling, friend, etc.
            $table->string('phone');
            $table->string('email')->nullable();
            $table->boolean('is_primary')->default(false);
            $table->boolean('is_verified')->default(false);
            $table->string('verification_code')->nullable();
            $table->timestamp('verified_at')->nullable();
            $table->integer('priority')->default(1); // order to contact (1 = first)
            $table->timestamps();

            // Indexes
            $table->index(['user_id', 'is_primary']);
            $table->index(['user_id', 'priority']);
            $table->index(['user_id', 'is_verified']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('emergency_contacts');
    }
};
