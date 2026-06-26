<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ReportSheet extends Model
{
    public $incrementing = false;
    protected $keyType = 'string';

    protected $fillable = [
        'id',
        'student_id',
        'student_name',
        'class_level',
        'term',
        'scores',
        'student_average',
        'class_average',
        'attendance',
        'teacher_remark',
        'principal_remark',
    ];

    protected $casts = [
        'scores' => 'array',
        'psychomotor' => 'array',
        'cognitive' => 'array',
    ];
}
