<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateCertificationsTable extends Migration
{
    public function up()
    {
        Schema::create('certifications', function (Blueprint $table) {
            $table->id();

            $table->string('name');
            $table->string('industry')->nullable();
            $table->string('issuing_organization')->nullable();
            $table->text('description')->nullable();

            $table->timestamps();

            // Indexes
            $table->index('name');
            $table->index('industry');
        });

        Schema::create('worker_certifications', function (Blueprint $table) {
            $table->id();

            $table->foreignId('worker_id')->constrained('users')->onDelete('cascade');
            $table->foreignId('certification_id')->constrained('certifications')->onDelete('cascade');

            $table->string('certification_number')->nullable();
            $table->date('issue_date')->nullable();
            $table->date('expiry_date')->nullable();
            $table->string('document_url')->nullable(); // uploaded proof
            $table->boolean('verified')->default(false);
            $table->timestamp('verified_at')->nullable();

            $table->timestamps();

            // Indexes
            $table->index('worker_id');
            $table->index('certification_id');
            $table->index(['worker_id', 'certification_id']);
            $table->index('verified');
            $table->index('expiry_date');
        });
    }

    public function down()
    {
        Schema::dropIfExists('worker_certifications');
        Schema::dropIfExists('certifications');
    }
}
