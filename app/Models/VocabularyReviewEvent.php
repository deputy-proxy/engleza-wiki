<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class VocabularyReviewEvent extends Model
{
    protected $fillable = [
        'learner_vocabulary_item_id', 'user_id', 'grade', 'previous_interval_seconds',
        'next_interval_seconds', 'response_time_ms', 'reviewed_at',
    ];

    protected function casts(): array
    {
        return ['reviewed_at' => 'datetime'];
    }

    /** @return BelongsTo<LearnerVocabularyItem, $this> */
    public function item()
    {
        return $this->belongsTo(LearnerVocabularyItem::class, 'learner_vocabulary_item_id');
    }

    /** @return BelongsTo<User, $this> */
    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
