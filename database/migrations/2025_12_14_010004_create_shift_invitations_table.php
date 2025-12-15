<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateShiftInvitationsTable extends Migration
{
    public function up()
    {
        Schema::create('shift_invitations', function (Blueprint $table) {
            $table->id();

            $table->foreignId('shift_id')->constrained('shifts')->onDelete('cascade');
            $table->foreignId('worker_id')->constrained('users')->onDelete('cascade');
            $table->foreignId('invited_by')->constrained('users')->onDelete('cascade'); // business or agent

            $table->text('message')->nullable();
            $table->enum('status', ['pending', 'accepted', 'declined', 'expired'])->default('pending')->index();

            $table->timestamp('sent_at')->useCurrent();
            $table->timestamp('responded_at')->nullable();

            $table->timestamps();

            // Indexes
            $table->index('shift_id');
            $table->index('worker_id');
            $table->index(['worker_id', 'status']);
            $table->index('sent_at');

            // Unique constraint: can only invite once per shift/worker
            $table->unique(['shift_id', 'worker_id']);
        });
    }

    public function down()
    {
        Schema::dropIfExists('shift_invitations');
    }
}
