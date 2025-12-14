<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateShiftAttachmentsTable extends Migration
{
    public function up()
    {
        Schema::create('shift_attachments', function (Blueprint $table) {
            $table->id();

            $table->foreignId('shift_id')->constrained('shifts')->onDelete('cascade');
            $table->foreignId('uploaded_by')->constrained('users')->onDelete('cascade');

            $table->string('file_name');
            $table->string('file_path');
            $table->string('file_type');
            $table->integer('file_size'); // in bytes
            $table->text('description')->nullable();

            $table->timestamps();

            // Indexes
            $table->index('shift_id');
            $table->index('uploaded_by');
        });
    }

    public function down()
    {
        Schema::dropIfExists('shift_attachments');
    }
}
