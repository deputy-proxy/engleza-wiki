<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class WritingSubmission extends Model
{
    protected $fillable = ['writing_resource_id', 'user_id', 'content', 'word_count', 'submitted_at', 'status'];

    protected $casts = ['submitted_at' => 'datetime'];

    public function writingResource()
    {
        return $this->belongsTo(WritingResource::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
