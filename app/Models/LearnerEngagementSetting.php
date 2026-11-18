<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class LearnerEngagementSetting extends Model
{
    protected $fillable = ['user_id', 'daily_goal_xp', 'timezone'];

    protected function casts(): array
    {
        return ['daily_goal_xp' => 'integer'];
    }

    /** @return BelongsTo<User, $this> */
    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
