<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * @property-read \App\Models\Resource $resource
 * @property-read Collection<int, LearningAnswer> $answers
 */
class LearningAttempt extends Model
{
    protected $fillable = ['resource_id', 'user_id', 'session_id', 'started_at', 'completed_at', 'status', 'score', 'percentage', 'ip_address', 'user_agent', 'total_points', 'breakdown', 'question_order'];

    protected $casts = ['started_at' => 'datetime', 'completed_at' => 'datetime', 'total_points' => 'integer', 'breakdown' => 'array', 'question_order' => 'array'];

    /** @return BelongsTo<\App\Models\Resource, $this> */
    public function resource()
    {
        return $this->belongsTo(Resource::class);
    }

    /** @return BelongsTo<User, $this> */
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    /** @return HasMany<LearningAnswer, $this> */
    public function answers()
    {
        return $this->hasMany(LearningAnswer::class);
    }
}
