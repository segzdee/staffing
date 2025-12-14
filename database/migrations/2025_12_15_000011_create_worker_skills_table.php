<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateWorkerSkillsTable extends Migration
{
    public function up()
    {
        Schema::create('worker_skills', function (Blueprint $table) {
            $table->id();

            $table->foreignId('worker_id')->constrained('users')->onDelete('cascade');
            $table->foreignId('skill_id')->constrained('skills')->onDelete('cascade');

            $table->enum('proficiency_level', ['beginner', 'intermediate', 'advanced', 'expert'])->default('intermediate');
            $table->integer('years_experience')->default(0);
            $table->boolean('verified')->default(false);

            $table->timestamps();

            // Indexes
            $table->index('worker_id');
            $table->index('skill_id');
            $table->index(['worker_id', 'skill_id']);
            $table->unique(['worker_id', 'skill_id']);
        });
    }

    public function down()
    {
        Schema::dropIfExists('worker_skills');
    }
}
