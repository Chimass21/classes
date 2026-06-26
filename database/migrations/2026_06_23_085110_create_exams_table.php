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
        Schema::create('exams', function (Blueprint $table) {
            $table->string('id')->primary();
            $table->string('title');
            $table->string('subject');
            $table->string('level');
            $table->integer('duration')->default(30);
            $table->integer('total_marks')->default(0);
            $table->text('instructions')->nullable();
            $table->json('questions')->nullable();
            $table->string('creator_id');
            $table->string('creator_name');
            $table->boolean('is_published')->default(false);
            $table->string('exam_link')->nullable();
            $table->timestamps();
            $table->foreign('creator_id')->references('id')->on('users')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('exams');
    }
};
