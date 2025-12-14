<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateShiftSwapsTable extends Migration
{
    public function up()
    {
        Schema::create('shift_swaps', function (Blueprint $table) {
            $table->id();

            $table->foreignId('shift_assignment_id')->constrained('shift_assignments')->onDelete('cascade');
            $table->foreignId('offering_worker_id')->constrained('users')->onDelete('cascade'); // worker offering the shift
            $table->foreignId('receiving_worker_id')->nullable()->constrained('users')->onDelete('cascade'); // worker accepting the shift

            $table->text('reason')->nullable();
            $table->enum('status', ['pending', 'approved', 'rejected', 'cancelled'])->default('pending')->index();

            $table->boolean('business_approval_required')->default(true);
            $table->timestamp('business_approved_at')->nullable();
            $table->foreignId('approved_by')->nullable()->constrained('users')->onDelete('set null');

            $table->timestamps();

            // Indexes
            $table->index('shift_assignment_id');
            $table->index('offering_worker_id');
            $table->index('receiving_worker_id');
        });
    }

    public function down()
    {
        Schema::dropIfExists('shift_swaps');
    }
}
