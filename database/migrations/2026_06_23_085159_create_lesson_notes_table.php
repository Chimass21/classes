<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('lesson_notes', function (Blueprint $table) {
            $table->string('id')->primary();
            $table->string('teacher_id');
            $table->string('subject');
            $table->string('class_level');
            $table->string('topic');
            $table->string('sub_topic')->nullable();
            $table->integer('week')->default(1);
            $table->date('date');
            $table->string('periods')->default('2 Periods');
            $table->enum('difficulty', ['Easy', 'Medium', 'Hard'])->default('Medium');
            $table->json('content')->nullable();
            $table->timestamps();
            $table->foreign('teacher_id')->references('id')->on('users')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('lesson_notes');
    }
};
