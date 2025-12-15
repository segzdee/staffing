<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateConversationsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('conversations', function (Blueprint $table) {
            $table->id();
            $table->bigInteger('shift_id')->unsigned()->nullable();
            $table->bigInteger('worker_id')->unsigned();
            $table->bigInteger('business_id')->unsigned();
            $table->string('subject')->nullable();
            $table->enum('status', ['active', 'archived', 'closed'])->default('active');
            $table->timestamp('last_message_at')->nullable();
            $table->timestamps();

            $table->index(['worker_id', 'business_id']);
            $table->index('shift_id');
            $table->index('status');
            $table->index('last_message_at');

            // Unique constraint: one conversation per worker-business-shift combination
            $table->unique(['worker_id', 'business_id', 'shift_id'], 'unique_conversation');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('conversations');
    }
}
