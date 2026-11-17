<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class LearningPathEnrollment extends Model
{
    protected $fillable = ['learning_path_id', 'user_id', 'status', 'activated_at', 'completed_at'];

    protected $casts = ['activated_at' => 'datetime', 'completed_at' => 'datetime'];

    /** @return BelongsTo<LearningPath, $this> */
    public function path()
    {
        return $this->belongsTo(LearningPath::class, 'learning_path_id');
    }

    /** @return BelongsTo<User, $this> */
    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
