<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('results', function (Blueprint $table) {
            if (!Schema::hasColumn('results', 'time_spent')) {
                $table->integer('time_spent')->default(0)->after('total_questions');
            }
            if (!Schema::hasColumn('results', 'total_possible_marks')) {
                $table->integer('total_possible_marks')->default(0)->after('time_spent');
            }
        });
    }

    public function down(): void
    {
        Schema::table('results', function (Blueprint $table) {
            $table->dropColumn(['time_spent', 'total_possible_marks']);
        });
    }
};
