<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateWorkerPenaltiesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('worker_penalties', function (Blueprint $table) {
            $table->id();

            // Relationships
            $table->foreignId('worker_id')->constrained('users')->onDelete('cascade');
            $table->foreignId('shift_id')->nullable()->constrained('shifts')->onDelete('cascade');
            $table->foreignId('business_id')->nullable()->constrained('users')->onDelete('set null');
            $table->foreignId('issued_by_admin_id')->nullable()->constrained('users')->onDelete('set null');

            // Penalty details
            $table->enum('penalty_type', [
                'no_show',
                'late_cancellation',
                'misconduct',
                'property_damage',
                'policy_violation',
                'other'
            ])->index();

            $table->decimal('penalty_amount', 10, 2); // Amount in dollars
            $table->text('reason'); // Description of the violation
            $table->text('evidence_notes')->nullable(); // Admin notes about the evidence

            // Status tracking
            $table->enum('status', [
                'pending',
                'active',
                'appealed',
                'appeal_approved',
                'appeal_rejected',
                'waived',
                'paid'
            ])->default('pending')->index();

            // Payment tracking
            $table->boolean('is_paid')->default(false);
            $table->timestamp('paid_at')->nullable();
            $table->string('payment_method')->nullable(); // deducted, paid_directly, waived

            // Timestamps
            $table->timestamp('issued_at')->nullable();
            $table->timestamp('due_date')->nullable(); // Usually 7 days from issue
            $table->timestamps();
            $table->softDeletes();

            // Indexes for common queries
            $table->index('worker_id');
            $table->index('shift_id');
            $table->index('business_id');
            $table->index(['status', 'created_at']);
            $table->index(['worker_id', 'status']);
            $table->index('due_date');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('worker_penalties');
    }
}
