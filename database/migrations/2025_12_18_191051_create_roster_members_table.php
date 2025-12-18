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
        Schema::create('roster_members', function (Blueprint $table) {
            $table->id();
            $table->foreignId('roster_id')->constrained('business_rosters')->onDelete('cascade');
            $table->foreignId('worker_id')->constrained('users')->onDelete('cascade');
            $table->enum('status', ['active', 'inactive', 'pending'])->default('pending');
            $table->text('notes')->nullable();
            $table->decimal('custom_rate', 8, 2)->nullable();
            $table->integer('priority')->default(0);
            $table->json('preferred_positions')->nullable();
            $table->json('availability_preferences')->nullable();
            $table->foreignId('added_by')->constrained('users')->onDelete('cascade');
            $table->timestamp('last_worked_at')->nullable();
            $table->integer('total_shifts')->default(0);
            $table->timestamps();

            $table->unique(['roster_id', 'worker_id']);
            $table->index(['roster_id', 'status']);
            $table->index(['worker_id', 'status']);
            $table->index('priority');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('roster_members');
    }
};
