<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateAgencyWorkersTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('agency_workers', function (Blueprint $table) {
            $table->id();
            $table->bigInteger('agency_id')->unsigned();
            $table->bigInteger('worker_id')->unsigned();
            $table->decimal('commission_rate', 5, 2)->default(0.00);
            $table->enum('status', ['active', 'suspended', 'removed'])->default('active');
            $table->text('notes')->nullable();
            $table->timestamp('added_at')->nullable();
            $table->timestamp('removed_at')->nullable();
            $table->timestamps();

            $table->index('agency_id');
            $table->index('worker_id');
            $table->index('status');

            // Unique constraint: one relationship per agency-worker pair
            $table->unique(['agency_id', 'worker_id'], 'unique_agency_worker');

            // Foreign keys
            $table->foreign('agency_id')
                ->references('id')
                ->on('users')
                ->onDelete('cascade');

            $table->foreign('worker_id')
                ->references('id')
                ->on('users')
                ->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('agency_workers');
    }
}
