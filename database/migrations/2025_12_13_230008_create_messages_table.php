<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateMessagesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('messages', function (Blueprint $table) {
            $table->id();
            $table->bigInteger('conversation_id')->unsigned();
            $table->bigInteger('from_user_id')->unsigned();
            $table->bigInteger('to_user_id')->unsigned();
            $table->text('message');
            $table->boolean('is_read')->default(false);
            $table->timestamp('read_at')->nullable();
            $table->string('attachment_url')->nullable();
            $table->string('attachment_type')->nullable(); // image, document, etc.
            $table->timestamps();

            $table->index('conversation_id');
            $table->index(['to_user_id', 'is_read']);
            $table->index('created_at');

            $table->foreign('conversation_id')
                ->references('id')
                ->on('conversations')
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
        Schema::dropIfExists('messages');
    }
}
