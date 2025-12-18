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
        Schema::create('worker_suspensions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->enum('type', ['warning', 'temporary', 'indefinite', 'permanent']);
            $table->enum('reason_category', [
                'no_show',
                'late_cancellation',
                'misconduct',
                'policy_violation',
                'fraud',
                'safety',
                'other',
            ]);
            $table->text('reason_details');
            $table->foreignId('related_shift_id')->nullable()->constrained('shifts')->onDelete('set null');
            $table->foreignId('issued_by')->constrained('users')->onDelete('cascade');
            $table->timestamp('starts_at');
            $table->timestamp('ends_at')->nullable();
            $table->enum('status', ['active', 'completed', 'appealed', 'overturned', 'escalated'])->default('active');
            $table->boolean('affects_booking')->default(true);
            $table->boolean('affects_visibility')->default(false);
            $table->integer('strike_count')->default(1);
            $table->timestamps();

            // Indexes for common queries
            $table->index(['user_id', 'status']);
            $table->index(['status', 'ends_at']);
            $table->index('reason_category');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('worker_suspensions');
    }
};
