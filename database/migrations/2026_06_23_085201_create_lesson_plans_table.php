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
        Schema::create('lesson_plans', function (Blueprint $table) {
            $table->string('id')->primary();
            $table->string('teacher_id');
            $table->string('subject');
            $table->string('class_level');
            $table->string('topic');
            $table->string('sub_topic')->nullable();
            $table->integer('week')->default(1);
            $table->date('date')->nullable();
            $table->string('periods')->default('2 Periods');
            $table->string('school_name')->nullable();
            $table->string('teacher_name')->nullable();
            $table->string('term')->nullable();
            $table->string('duration')->default('40 Minutes');
            $table->string('age_of_pupils')->nullable();
            $table->string('number_of_pupils')->nullable();
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
        Schema::dropIfExists('lesson_plans');
    }
};
