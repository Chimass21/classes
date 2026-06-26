<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class LessonNote extends Model
{
    public $incrementing = false;
    protected $keyType = 'string';

    protected $fillable = [
        'id',
        'teacher_id',
        'subject',
        'class_level',
        'topic',
        'sub_topic',
        'periods',
        'difficulty',
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
