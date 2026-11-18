<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class LearnerDailyEngagement extends Model
{
    protected $fillable = ['user_id', 'activity_date', 'xp_earned', 'goal_xp', 'goal_completed_at'];

    protected function casts(): array
    {
        return [
            'activity_date' => 'immutable_date',
            'xp_earned' => 'integer',
            'goal_xp' => 'integer',
            'goal_completed_at' => 'datetime',
        ];
    }

    /** @return BelongsTo<User, $this> */
    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
