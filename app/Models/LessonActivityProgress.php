<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class LessonActivityProgress extends Model
{
    protected $fillable = ['lesson_activity_id', 'user_id', 'status', 'completed_at'];

    protected $casts = ['completed_at' => 'datetime'];

    /** @return BelongsTo<LessonActivity, $this> */
    public function activity()
    {
        return $this->belongsTo(LessonActivity::class, 'lesson_activity_id');
    }

    /** @return BelongsTo<User, $this> */
    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
