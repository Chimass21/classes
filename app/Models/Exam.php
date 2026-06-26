<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Exam extends Model
{
    public $incrementing = false;
    protected $keyType = 'string';

    protected $fillable = [
        'id',
        'title',
        'subject',
        'level',
        'duration',
        'total_marks',
        'instructions',
        'questions',
        'creator_id',
        'creator_name',
        'exam_link',
        'is_published',
    ];

    protected $casts = [
        'questions' => 'array',
        'is_published' => 'boolean',
    ];

    public function creator()
    {
        return $this->belongsTo(User::class, 'creator_id');
    }

    public function results()
    {
        return $this->hasMany(Result::class);
    }
}
