<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class LearnerAchievement extends Model
{
    protected $fillable = ['user_id', 'achievement_key', 'title', 'description', 'evidence', 'awarded_at'];

    protected function casts(): array
    {
        return ['evidence' => 'array', 'awarded_at' => 'datetime'];
    }

    /** @return BelongsTo<User, $this> */
    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
