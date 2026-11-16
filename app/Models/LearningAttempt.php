<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class LearningAttempt extends Model
{
    protected $fillable = ['resource_id', 'user_id', 'session_id', 'started_at', 'completed_at', 'status', 'score', 'percentage', 'ip_address', 'user_agent'];

    protected $casts = ['started_at' => 'datetime', 'completed_at' => 'datetime'];

    public function resource()
    {
        return $this->belongsTo(Resource::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function answers()
    {
        return $this->hasMany(LearningAnswer::class);
    }
}
