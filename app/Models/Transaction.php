<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Transaction extends Model
{
    public $incrementing = false;
    protected $keyType = 'string';

    protected $fillable = [
        'id',
        'user_id',
        'user_name',
        'amount',
        'type',
        'purpose',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
