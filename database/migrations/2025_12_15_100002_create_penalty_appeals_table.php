<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreatePenaltyAppealsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('penalty_appeals', function (Blueprint $table) {
            $table->id();

            // Relationships
            $table->foreignId('penalty_id')->constrained('worker_penalties')->onDelete('cascade');
            $table->foreignId('worker_id')->constrained('users')->onDelete('cascade');
            $table->foreignId('reviewed_by_admin_id')->nullable()->constrained('users')->onDelete('set null');

            // Appeal details
            $table->text('appeal_reason'); // Worker's explanation
            $table->json('evidence_urls')->nullable(); // Photos, documents uploaded to cloud
            $table->text('additional_notes')->nullable(); // Any additional context

            // Status tracking
            $table->enum('status', [
                'pending',
                'under_review',
                'approved',
                'rejected'
            ])->default('pending')->index();

            // Review details
            $table->text('admin_notes')->nullable(); // Internal admin notes
            $table->text('decision_reason')->nullable(); // Reason for approval/rejection
            $table->decimal('adjusted_amount', 10, 2)->nullable(); // If partially approved

            // Timestamps
            $table->timestamp('submitted_at')->nullable();
            $table->timestamp('reviewed_at')->nullable();
            $table->timestamp('deadline_at')->nullable(); // 14 days from penalty issue
            $table->timestamps();
            $table->softDeletes();

            // Indexes for common queries
            $table->index('penalty_id');
            $table->index('worker_id');
            $table->index(['status', 'submitted_at']);
            $table->index('reviewed_by_admin_id');
            $table->index('deadline_at');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('penalty_appeals');
    }
}
