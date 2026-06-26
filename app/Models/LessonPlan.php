<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class LessonPlan extends Model
{
    public $incrementing = false;
    protected $keyType = 'string';

    protected $fillable = [
        'id',
        'teacher_id',
        'school_name',
        'teacher_name',
        'class_level',
        'subject',
        'topic',
        'sub_topic',
        'term',
        'duration',
        'week',
        'content',
    ];

    protected $casts = [
        'content' => 'array',
    ];

    public function teacher()
    {
        return $this->belongsTo(User::class, 'teacher_id');
    }
}
