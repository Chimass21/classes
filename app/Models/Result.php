<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Result extends Model
{
    public $incrementing = false;
    protected $keyType = 'string';

    protected $fillable = [
        'id',
        'exam_id',
        'exam_title',
        'subject',
        'student_id',
        'student_name',
        'score',
        'percentage',
        'total_questions',
        'correct_answers',
        'failed_questions',
        'time_spent',
        'total_possible_marks',
    ];

    protected $casts = [
        'failed_questions' => 'array',
    ];

    public function exam()
    {
        return $this->belongsTo(Exam::class);
    }

    public function student()
    {
        return $this->belongsTo(User::class, 'student_id');
    }
}
