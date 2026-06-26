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
        Schema::create('report_sheets', function (Blueprint $table) {
            $table->string('id')->primary();
            $table->string('student_id');
            $table->string('student_name');
            $table->string('class_level');
            $table->string('term');
            $table->integer('attendance')->default(0);
            $table->json('scores')->nullable();
            $table->decimal('student_average', 5, 2)->default(0);
            $table->decimal('class_average', 5, 2)->default(0);
            $table->json('psychomotor')->nullable();
            $table->json('cognitive')->nullable();
            $table->text('teacher_remark')->nullable();
            $table->text('principal_remark')->nullable();
            $table->timestamps();
            $table->foreign('student_id')->references('id')->on('users')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('report_sheets');
    }
};
