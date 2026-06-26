<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class QuestionSet extends Model
{
    public $incrementing = false;
    protected $keyType = 'string';

    protected $fillable = [
        'id',
        'teacher_id',
        'source',
        'source_id',
        'questions',
    ];

    protected $casts = [
        'questions' => 'array',
    ];

    public function teacher()
    {
        return $this->belongsTo(User::class, 'teacher_id');
    }
}
